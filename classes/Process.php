<?php
class Process extends App {  
	
	
	function transactions(){
		$this->loadModel("processModel");
		$this->loadModel("deroApiModel");
		$this->loadModel("webApiModel");
		$this->loadModel("productModel");



		//Retry all failed webapi calls.
		$this->webApiModel->tryPending();
		//Try checkin if it is time.
		if($this->processModel->nextCheckInTime()){
			$this->webApiModel->checkIn();
		}			
		
		$this->processModel->setInstalledTime();
		//Define arrays for returning to frond end.
		$product_changes = false;
		//$return_actions = [];
		$messages = [];
		$errors = [];
		
		
		/*******************************/
		/** Check if pending response **/
		/** transfers have confirmed  **/
		/*******************************/
		$t_block_height='';
		$result_str = $this->deroApiModel->getHeight();
		$heightRes = json_decode($result_str);
		if(!isset($heightRes->errors) && isset($heightRes->result)){	
			$t_block_height = $heightRes->result->height;	
			--$t_block_height;
		}		
				
		
		
		$given = new DateTime();
		$given->setTimezone(new DateTimeZone("UTC"));	
		$given->modify('-36 seconds');//Ensure that one block has passed...
		$time_utc = $given->format("Y-m-d H:i:s");
		
		$unConfirmed = $this->processModel->unConfirmedResponses();

		$confirmed_txns=[];
		//go through the responses that haven't been confirmed.
		//keep old txids in a csv and check if any of those have confirmed, if so update the txid with the first one that confirms... 
		foreach($unConfirmed as $response){
			
			//make sure the response is at least one block old before checking.
			if($response['time_utc'] < $time_utc && $response['t_block_height'] < $t_block_height){
				$confirmed = false;
				$alltxids=[];
				$alltxids= explode(",",$response['txids']);
				
				foreach($alltxids as $rtxid){
					if(!$confirmed){
						$check_transaction_result = $this->deroApiModel->getTransferByTXID($rtxid);
						$check_transaction_result = json_decode($check_transaction_result);
					
						
						//succesfully confirmed 
						if(!isset($check_transaction_result->errors) && isset($check_transaction_result->result)){		
							$confirmed = true;
							$this->processModel->markResAsConfirmed($response['txid']);	
							
							if($response['txid'] == $rtxid){
								//Same txid, good to go!
								$confirmed_txns[] = $response['txid'];
							}else{									
								//Update the txid to the first one that confirmed. (allow later ones to fail and not be retried)
								$this->processModel->updateResponseTXID($response['txid'],$rtxid);								
								$confirmed_txns[] = $rtxid;
							}							
						}
					}
				}
				
				if(!$confirmed){
					//set the incoming to not processed, keep response record.
					$this->processModel->markOrderAsPending($response['txid']);	
				}
			}
		}

		foreach($confirmed_txns as $txid){
			//Get record for freshly confirmed transfer with txid
			$confirmed_incoming= $this->processModel->getConfirmedInc($txid);

			foreach($confirmed_incoming as $record){
				//does not inform when there is an inactive ia since it uses product id 0 and can't find the details...
				$messages[] = $record['type']." confirmed for order txid:".$record['txid'];	
				
				//send post message to web api here... 
				if($record['out_message_uuid'] == 1 && $record['type'] == 'sale'){
					
					// uuid is in $record['response_out_message']
					// custom API Address is in  = $record['out_message'];	

					$res = $this->webApiModel->newTX($record);	
					
				}else if($record['type'] == 'sc_refund_not_enough_tokens'){
					//Out of tokens refund confirmed... set i address to inactive
					$this->loadModel('editProductModel');
					if($record['for_ia_id'] != ''){
						$this->editProductModel->toggleIAddr($record['for_ia_id'],0);
						//Send update through web api.
						$ia = $this->productModel->getIAddressById($record['for_ia_id']);
						$this->webApiModel->submitIAddress($ia);
						
						$product_changes = true;
					}
				}
				
			}
		}



		/******************************/
		/** Check incoming transfers **/
		/** for new sales            **/
		/******************************/
		$address_submission_candidates=[];
		//Get transfers and save them if they are new and later than the db creation time.	
		$export_transfers_result = $this->deroApiModel->getTransfers();

	//	$export_transfers_result =file_get_contents('testjson.json');
		
		$export_transfers_result = json_decode($export_transfers_result);
		if($export_transfers_result === NULL){
			$errors[] = "Wallet Connection Error.";
		}else{

			foreach($export_transfers_result->result->entries as $entry){		
				//See if there is a payload... todo: check if it is a shipping address submission
				if(isset($entry->payload_rpc)){
					
				
					$tx = $this->processModel->makeTxObject($entry);
					
					if($tx !==false){
						$p_and_ia_ids = $this->processModel->insertNewTransaction($tx); //and do inventory first...						
						//check type of inventory update... product or iaddress
						if($p_and_ia_ids !== false){
							$product_changes = true;
							//set changes to true to reload the products
							if($p_and_ia_ids['id_type'] == 'p'){
								$this->webApiModel->submitProduct($this->productModel->getProductById($p_and_ia_ids['p']));	
								//$return_actions['inv_pids'][]=$p_and_ia_ids['p'];
							}else{				
								$ia = $this->productModel->getIAddressById($p_and_ia_ids['ia']);
								$this->webApiModel->submitIAddress($ia);
								//$return_actions['inv_p_iids'][]=['product_id'=>$ia['product_id'],'i_address_id'=>$p_and_ia_ids['ia']];					
							}
						}
					}else{
						$address_submission_candidates[]=$entry;	
						
					}
				}
			}
		}	
			
		$address_arrays=[];
		//Now do address submissions since old address submissions need to be filtered out first.
		if(!empty($address_submission_candidates)){
			foreach($address_submission_candidates as $entry){				
				$res = $this->processModel->getAddressArray($entry);	
				if($res !==false){
					$address_arrays[] = $res;
				}						
			}
			
			$filtered = [];
			foreach($address_arrays as $address){				
				$filtered[$address['id']] = $address;	
			}
			
			foreach($filtered as $latest_submission){
				//It is an address submission possibly
				$saved = $this->processModel->saveAddress($latest_submission);
				if($saved !== false){
					$messages[] = "Shipping address submitted by buyer.";
				}
			}				
		}

			

		//Make array of unprocessed transactions
		$notProcessed=[];
		$new=$this->processModel->unprocessedTxs();
		if($new !== false){
			$notProcessed = $new;
		}

		/*************************/
		/** Do regular products **/
		/*************************/

		$tx_list = [];

		foreach($notProcessed as $tx){
			
			$settings = $this->processModel->getIAsettings($tx);
	
			$successful=false;
			//Enusre it was a successful incoming transaction.
			if($tx['successful'] == 1){
				$successful = true;
			}	
			if($successful){
				//Was found and had enough inventory.
				if($settings['scid'] == '' && $settings['ia_scid'] == ''){
					if($settings['p_type'] == 'physical'){
						$tx_list['physical_sales'][] = $tx;
					}else{
						$tx_list['digital_sales'][] = $tx;
					}					
				}else{
					$tx_list['sc_sales'][] = $tx;
				}
			
			}else if($settings !== false){
				//No inventory
		
				if($settings['scid'] == '' && $settings['ia_scid'] == ''){
					$tx_list['refunds'][] = $tx;
				}else{
					$tx_list['sc_refunds'][] = $tx;
				}
				
			}else{
				//No mathcing products / I. Addresses found
				$tx_list['refunds'][] = $tx;
				
			}
			
			
		}	
		unset($notProcessed);




	//Combine orders from same wallet and block
	if(isset($tx_list['physical_sales'])){		
		$heights = [];
		foreach($tx_list['physical_sales'] as $tx){
			$heights[$tx['block_height']][] = $tx;
		}
		$blocks = [];
		foreach($heights as $height => $tx_array){
			foreach($tx_array as $tx){
				$blocks[$height][$tx['buyer_address']][] = $tx;
			}	
		}
		$orders = [];
		foreach($blocks as $block => $addresses){
			foreach($addresses as $tx_array){
				$this->processModel->insertOrder($tx_array,'physical_sale');
			}
		}
	}
	
	//Create digital sales as separate orders.
	if(isset($tx_list['digital_sales'])){		
		foreach($tx_list['digital_sales'] as $tx){		
			$this->processModel->insertOrder([$tx],'digital_sale');
		}
	}		
	//Create refund orders.
	if(isset($tx_list['refunds'])){		
		foreach($tx_list['refunds'] as $tx){		
			$this->processModel->insertOrder([$tx],'refund');
		}
	}
	
	
	//Create sc_sales orders.
	if(isset($tx_list['sc_sales'])){		
		foreach($tx_list['sc_sales'] as $tx){
			$this->processModel->insertOrder([$tx],'sc_sale');
		}
	}
	
	//Create sc refund orders.
	if(isset($tx_list['sc_refunds'])){		
		foreach($tx_list['sc_refunds'] as $tx){
			$this->processModel->insertOrder([$tx],'sc_refund');
		}
	}





	
	$transfer_list = [];
	$pending_orders = [];	
	
	$pending_physical_sale_orders = $this->processModel->getOrdersByStatusAndType('pending','physical_sale');	
	foreach($pending_physical_sale_orders as &$tx){
		$settings = $this->processModel->getIAsettings($tx);		
		$xfer = $this->createTransfer($tx,$settings);
		$tx = $xfer->tx;
		$transfer_list[] = $xfer->xfer;
	}
	unset($tx);
	
	$pending_digital_sale_orders = $this->processModel->getOrdersByStatusAndType('pending','digital_sale');	
	foreach($pending_digital_sale_orders as &$tx){
		$settings = $this->processModel->getIAsettings($tx);		
		$xfer = $this->createTransfer($tx,$settings);
		$tx = $xfer->tx;
		$transfer_list[] = $xfer->xfer;
	}
	unset($tx);
	
	$pending_orders = array_merge($pending_physical_sale_orders,$pending_digital_sale_orders);
	
	$pending_refund_orders = $this->processModel->getOrdersByStatusAndType('pending','refund');	
	foreach($pending_refund_orders as &$tx){
		$settings = $this->processModel->getIAsettings($tx);		
		$xfer = $this->createRefundTransfer($tx,$settings);
		$tx = $xfer->tx;
		$transfer_list[] = $xfer->xfer;
	}
	unset($tx);
	
	

	$pending_orders = array_merge($pending_orders,$pending_refund_orders);



		/***************************************/
		/** Combine Regular Product Transfers **/
		/***************************************/
		$responseTXID='';
		$t_block_height ='';
		/* Does combined transfers, scid transfers may require separate transfers in case of refund required...
		*/
		if(!empty($transfer_list)){
			//Make sure wallet is working
			$result_str = $this->deroApiModel->getHeight();
			$heightRes = json_decode($result_str);
			if(!isset($heightRes->errors) && isset($heightRes->result)){	
				$t_block_height = $heightRes->result->height;	
			}			
			
			$payload_result =null;
			if(is_int($t_block_height)){
				
				

				//try the transfer
				$payload_result = $this->deroApiModel->transfer($transfer_list);
				$payload_result = json_decode($payload_result);
				
				//Get the actual blockheight or just increment by 1 if it fails since we need to have a height to check for confirmation
				$tbh = '';
				$result_str = $this->deroApiModel->getHeight();
				$heightRes = json_decode($result_str);
				if(!isset($heightRes->errors) && isset($heightRes->result)){	
					$tbh = $heightRes->result->height;							
				}					
				if(is_int($tbh)){
					$t_block_height = $heightRes->result->height;	
				}else{
					++$t_block_height;
				}		
			}
				

			if($payload_result != null && isset($payload_result->result)){
				$responseTXID = $payload_result->result->txid;
					
			}else{
				if(isset($payload_result->error)){
					$errors[] = "Error: ".$payload_result->error->message;
				}else{
					$errors[] = "Unkown Transfer Error";
				}
			}
		}


		if(empty($errors) && $responseTXID !== ''){
			foreach($pending_orders as $tx){
				
				//Mark incoming transaction as processed. 
				//In the next check cycle it can be set to unprocessed above if response is not confirmed, then it is reprocessed. 
				//Inventory is done once when it is first inserted.				
				$result = $this->processModel->markOrderAsProcessed($tx['order_id']);
					
				$given = new DateTime();
				$given->setTimezone(new DateTimeZone("UTC"));	
				$time_utc = $given->format("Y-m-d H:i:s");
				//could save time of next block instead of waiting 18 seconds for a confirmation check turbo mode
				if($result !== false){
					$response = (object)[
					"order_id"=>$tx['order_id'],
					"txid"=>$responseTXID,
					"type"=>$tx['type'],
					"buyer_address"=>$tx['buyer_address'],
					"out_amount"=>$tx['respond_amount'],
					"port"=>$tx['port'],
					"out_message"=>$tx['out_message'],
					"out_message_uuid"=>$tx['out_message_uuid'],
					"uuid"=>$tx['uuid'],
					"api_url"=>$tx['api_url'],
					"out_scid"=>$tx['out_scid'],
					"crc32"=>$tx['crc32'],
					"time_utc"=>$time_utc,
					"t_block_height"=>$t_block_height,
					];
					
					
					$this->processModel->saveResponse($response);
					$message_part='';
					if($tx['type'] == 'sale'){
						$detail_set = $this->processModel->getOrderDetails($tx['order_id']);
						foreach($detail_set as $details){
							$message_part .= $details['product_label'].', ';
						}
						$message_part = rtrim($message_part,', ');
					}else{
						$message_part = $tx['product_label'];
					}
					
					$messages[] = "{$tx['type']} response initiated". ($tx['type'] == 'sale' ? ' for "'.$message_part.'"' : '') . ".";
					
				}
			}
		}







		/*******************************/
		/***  Do smart contracts as  ***/
		/***  separate transfers     ***/
		/*******************************/
			
		//transfer_list	

		if(empty($transfer_list)){//Done with regular response transfer
		
			$pending_sc_orders = $this->processModel->getOrdersByStatusAndType('pending','sc_sale');	
			$pending_sc_refunds = $this->processModel->getOrdersByStatusAndType('pending','sc_refund');	
			$pending_orders = array_merge($pending_sc_orders,$pending_sc_refunds);
		
			$sent_one = false;			
			foreach($pending_orders as &$tx){
				if($sent_one){
					break;
				}
				$settings = $this->processModel->getIAsettings($tx);	

				$successful=false;
				if($settings !== false){	
					//Make sure it is a token sale...
					$successful = $tx['successful'];								
				}
			
				if($successful==1){
					//Is a smart contract token transfer...				
					//Send Response to buyer
					$xfer = $this->createSCTransfer($tx,$settings);	
					$tx = $xfer->tx;
					$sc_transfer = $xfer->xfer;

				}else if($successful==0){

					//No mathcing products with inv. / I. Addresses found
					//Send Refund to buyer
					$xfer = $this->createRefundSCTransfer($tx,$settings);	
					$tx = $xfer->tx;
					$sc_transfer = $xfer->xfer;		
					
				}else if($successful==2){

					$xfer = $this->createRefundSCTransfer($tx,$settings);	
					$tx = $xfer->tx;
					$sc_transfer = $xfer->xfer;		
				
					$tx['type'] = "sc_refund_not_enough_tokens";
					
				} 
				
			
				$responseTXID='';
				$t_block_height='';
					
				//Make sure wallet is working
				$result_str = $this->deroApiModel->getHeight();
				$heightRes = json_decode($result_str);
				if(!isset($heightRes->errors) && isset($heightRes->result)){	
					$t_block_height = $heightRes->result->height;	
				}			
				
				$payload_result =null;
				if(is_int($t_block_height)){
					//try the transfer
					$payload_result = $this->deroApiModel->transfer([$sc_transfer]);
					$payload_result = json_decode($payload_result);
					
					//Get the actual blockheight or just increment by 1 if it fails since we need to have a height to check for confimation
					$tbh = '';
					$result_str = $this->deroApiModel->getHeight();
					$heightRes = json_decode($result_str);
					if(!isset($heightRes->errors) && isset($heightRes->result)){	
						$tbh = $heightRes->result->height;							
					}					
					if(is_int($tbh)){
						$t_block_height = $heightRes->result->height;	
					}else{
						++$t_block_height;
					}		
				}
			
				
				$sent_one = true;
				if($payload_result != null && isset($payload_result->result)){
					
					$responseTXID = $payload_result->result->txid;
					
				}else{
					if(isset($payload_result->error)){
						if(strstr($payload_result->error->message, "Insufficent funds")){
							$errors[] = "SC Transfer Error. ".$payload_result->error->message;					
							// Save incoming as successful = 2 to signal it was a not enough tokens error...Reprocess...
							$this->processModel->markIncSuccessfulTwo($tx['id']);		
						}else{
							$errors[] = "SC Transfer Error. ".$payload_result->error->message;
						}
					}else{						
						$errors[] = "Unkown Transfer Error";
					}
				}
				
				if($responseTXID !== ''){
					$result = $this->processModel->markOrderAsProcessed($tx['order_id']);
						
					$given = new DateTime();
					$given->setTimezone(new DateTimeZone("UTC"));	
					$time_utc = $given->format("Y-m-d H:i:s");
					

					if($result !== false){
						$response = (object)[
						"order_id"=>$tx['order_id'],
						"txid"=>$responseTXID,
						"type"=>$tx['type'],
						"buyer_address"=>$tx['buyer_address'],
						"out_amount"=>$tx['respond_amount'],
						"port"=>$tx['port'],
						"out_message"=>$tx['out_message'],
						"out_message_uuid"=>$tx['out_message_uuid'],
						"uuid"=>$tx['uuid'],
						"api_url"=>$tx['api_url'],
						"out_scid"=>$tx['out_scid'],
						"crc32"=>$tx['crc32'],
						"time_utc"=>$time_utc,
						"t_block_height"=>$t_block_height
						];
						
						
					$this->processModel->saveResponse($response);
						
					$messages[] = "{$tx['type']} response initiated for: {$tx['respond_amount']}". ($tx['type'] == 'sc_sale' ? ' for "'.$tx['ia_comment'].'"' : '') . ".";
				
					}
				
				}
				
			}

			unset($tx);

		}




		if($product_changes){
			$product_results = $this->productModel->getProductsList();
			foreach ($product_results as &$product){
				$product['iaddress'] = $this->productModel->getIAddresses($product['id']);		
			}
			return ["success"=>true,"messages"=>$messages,"errors"=>$errors,"products"=>$product_results];
		}
		return ["success"=>true,"messages"=>$messages,"errors"=>$errors];

	}
	
	
	public function createTransfer(&$tx,$settings){

		//Send Response to buyer
		$transfer['respond_amount'] = $settings['respond_amount'];
		$transfer['address'] = $tx['buyer_address'];	
			
		$unique_identifier ='';
		//See if use uuid is selected, generate one if so.
		if($settings['out_message_uuid'] == 1){
			$UUID = new UUID;
			$unique_identifier = $UUID->v4();
			$settings['out_message'] = $settings['out_message'] . $unique_identifier;
		}
			
		//Use original out message if not a uuid (usually a link or some text)...
		$transfer['out_message'] = $settings['out_message'];				
			
		//Check for a pending response for this incoming tx
		$pending_response = $this->processModel->checkForResponseById($tx['id']);
		if($pending_response !==false){
			//Found a previous repsonse, use that instead of a new one (in case of double response we want the same confirmation number for address submission)
			$transfer['out_message'] = $pending_response['out_message'];
			$unique_identifier = $pending_response['out_message'];
				
		}
		$transfer['scid'] = "0000000000000000000000000000000000000000000000000000000000000000";				
		$transfer_object=(object)$transfer;
		//update unprocessed array
		$tx['ia_comment'] = $settings['ia_comment'];
		$tx['respond_amount'] = $transfer['respond_amount'];
		$tx['out_message'] = $transfer['out_message'];
		$tx['out_message_uuid'] = $settings['out_message_uuid'];
		$tx['uuid'] = $unique_identifier;
		$tx['api_url'] = $settings['api_url'];
		$tx['out_scid']=$transfer['scid'];
		$tx['crc32'] = ($unique_identifier == ''?1:crc32($unique_identifier));
		$tx['type'] = "sale";
			
		return (object)["tx"=>$tx,"xfer"=>$transfer_object];
	}
	
	public function createRefundTransfer(&$tx,$settings){	
		$transfer['respond_amount'] = $tx['amount'];
		$transfer['address'] = $tx['buyer_address'];	
		$transfer['out_message'] = substr("Refund for: ". $tx['product_label'],0,100);	
		$transfer['scid'] = "0000000000000000000000000000000000000000000000000000000000000000";
		$transfer_object=(object)$transfer;	
		//update unprocessed array
		$tx['respond_amount'] =  $tx['amount'];
		$tx['out_message'] = $transfer['out_message'];				
		$tx['out_message_uuid'] = '';
		$tx['uuid'] = '';
		$tx['api_url'] = '';				
		$tx['out_scid']=$transfer['scid'];				
		$tx['crc32'] = '';
		$tx['type'] = "refund";
		
		return (object)["tx"=>$tx,"xfer"=>$transfer_object];
	}
	
	public function createSCTransfer(&$tx,$settings){		
		//See if use uuid is selected, generate one if so.
		$unique_identifier = '';
		if($settings['out_message_uuid'] == 1){
			$UUID = new UUID;
			$unique_identifier = $UUID->v4();
			$settings['out_message'] = $settings['out_message'] . $unique_identifier;
		}
					
		//Use original out message if not a uuid (usually a link or some text)...
		$transfer['out_message'] = $settings['out_message'];	

		//Use Integrated Address scid if defined.
		$transfer['scid'] = $settings['scid'];
		if($settings['ia_scid'] != ''){
			$transfer['scid'] = $settings['ia_scid'];
		}
		//Use scid as out message if message is null.
		if($transfer['out_message'] == ''){
			$transfer['out_message'] = $transfer['scid'];
		}
		
		//Use Integrated Address respond amount if defined.
		$transfer['respond_amount'] = $settings['respond_amount'];//
		if($settings['ia_respond_amount'] !== '' && $settings['ia_respond_amount'] > 0){
			$transfer['respond_amount'] = $settings['ia_respond_amount'];
		}

		$transfer['address'] = $tx['buyer_address'];	
	
			
		//Check for a pending response for this incoming tx
		$pending_response = $this->processModel->checkForResponseById($tx['id']);
		if($pending_response !==false){
			//Found a previous repsonse, use that instead of a new one (in case of double response we want the same confirmation number for address submission)
			$transfer['out_message'] = $pending_response['out_message'];
			$unique_identifier = $pending_response['out_message'];
		}
			
		$transfer_object=(object)$transfer;
		//update unprocessed array
		$tx['ia_comment'] = $settings['ia_comment'];
		$tx['respond_amount'] = $transfer['respond_amount'];
		$tx['out_message'] = $transfer['out_message'];
		$tx['out_message_uuid'] = $settings['out_message_uuid'];
		$tx['uuid'] = $unique_identifier;
		$tx['api_url'] = $settings['api_url'];
		$tx['out_scid']=$transfer['scid'];
		$tx['crc32'] = ($unique_identifier == ''?1:crc32($unique_identifier));//not really required for token xfers...
		$tx['type'] = "sc_sale";
	
		return (object)["tx"=>$tx,"xfer"=>$transfer_object];
	}


	public function createRefundSCTransfer(&$tx,$settings){	
		//No mathcing products / I. Addresses found
		//Send Refund to buyer
		
		$transfer['respond_amount'] = $tx['amount'];
		$transfer['address'] = $tx['buyer_address'];	
		$transfer['out_message'] = substr("Refund for: ". $tx['product_label'],0,100);	
		$transfer['scid'] = "0000000000000000000000000000000000000000000000000000000000000000";
		$transfer_object=(object)$transfer;	
		//update unprocessed array
		$tx['respond_amount'] =  $tx['amount'];
		$tx['out_message'] = $transfer['out_message'];				
		$tx['out_message_uuid'] = '';
		$tx['uuid'] = '';
		$tx['api_url'] = '';
		$tx['out_scid']=$transfer['scid'];
		$tx['crc32'] = '';
		$tx['type'] = "sc_refund";

		return (object)["tx"=>$tx,"xfer"=>$transfer_object];
	}





	
}

