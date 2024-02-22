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
		$given = new DateTime();
		$given->setTimezone(new DateTimeZone("UTC"));	
		$given->modify('-36 seconds');//Ensure that one block has passed...
		$time_utc = $given->format("Y-m-d H:i:s");
		
		$unConfirmed = $this->processModel->unConfirmedTxs();

		$confirmed_txns=[];
		//go through the responses that haven't been confirmed.
		//keep old txids in a csv and check if any of those have confirmed, if so update the txid with the first one that confirms... 
		foreach($unConfirmed as $response){
			//make sure the response is at least one block old before checking.
			if($response['time_utc'] < $time_utc){
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
					$this->processModel->markIncAsNotProcessed($response['txid']);	
				}
			}
		}

		foreach($confirmed_txns as $txid){
			//Get record for freshly confirmed transfer with txid
			$confirmed_incoming = $this->processModel->getConfirmedInc($txid);

			foreach($confirmed_incoming as $record){
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
						$ia = $this->productModel->getIAddressById($record['ia_id']);
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

		//Get transfers and save them if they are new and later than the db creation time.	
		$export_transfers_result = $this->deroApiModel->getTransfers();
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
							//set chenges to true to reload the products
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
							
						//It is an address submission possibly
						$saved = $this->processModel->addressSubmission($entry);
						if($saved !== false){
							$messages[] = "Shipping address submitted by buyer.";
						}
					}
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

		$transfer_list = [];

		foreach($notProcessed as &$tx){
			
			$settings = $this->processModel->getIAsettings($tx);
			if($settings['scid'] != ''){
				continue 1;
			}else if($settings['ia_scid'] != ''){
				continue 1;
			}
			$transfer=[];
			$successful=false;
			if($settings !== false){
				if($tx['successful'] == 1){
					$successful = true;
				}				
			}
			
			if($successful){
				//Was found and had enough inventory.
				$xfer = $this->createTransfer($tx,$settings);
				$tx = $xfer->tx;
				$transfer_list[] = $xfer->xfer;

			}else{
				//No mathcing products / I. Addresses found
				//Send Refund to buyer
				$xfer = $this->createRefundTransfer($tx,$settings);
				$tx = $xfer->tx;
				$transfer_list[] = $xfer->xfer;
			}
			
			
		}	
		unset($tx);


		/***************************************/
		/** Combine Regular Product Transfers **/
		/***************************************/
		$responseTXID='';
		/* Does combined transfers, scid transfers may require separate transfers in case of refund required...
		*/
		if(!empty($transfer_list)){
						
			$payload_result = $this->deroApiModel->transfer($transfer_list);
			$payload_result = json_decode($payload_result);

			if($payload_result != null && isset($payload_result->result)){
				$responseTXID = $payload_result->result->txid;
			}else{
				if($payload_result->error){
					$errors[] = "Error: ".$payload_result->error->message;
				}else{
					$errors[] = "Unkown Transfer Error";
				}
			}
		}


		if(empty($errors) && $responseTXID !== ''){
			foreach($notProcessed as $tx){
				//
				///check this!!
				//Not set, not a regular product...
				if(! isset($tx['out_scid'])){
					continue 1;
					//
				}else{
					if(!($tx['type'] == 'sale' || $tx['type'] == 'refund') ){
						continue 1;
					}
				}
				//Mark incoming transaction as processed. 
				//In the next check cycle it can be set to unprocessed above if response is not confirmed, then it is reprocessed. 
				//Inventory is done once when it is first inserted.				
				$result = $this->processModel->markAsProcessed($tx['txid']);
					
				$given = new DateTime();
				$given->setTimezone(new DateTimeZone("UTC"));	
				$time_utc = $given->format("Y-m-d H:i:s");
				//could save time of next block instead of waiting 18 seconds for a confirmation check turbo mode
				if($result !== false){
					$response = (object)[
					"incoming_id"=>$tx['id'],
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
					"time_utc"=>$time_utc
					];
					
					
					$this->processModel->saveResponse($response);
					
					$messages[] = "{$tx['type']} response initiated". ($tx['type'] == 'sale' ? ' for "'.$tx['ia_comment'].'"' : '') . ".";
					
				}
			}
		}



		/*******************************/
		/***  Do smart contracts as  ***/
		/***  separate transfers     ***/
		/*******************************/
			
		//transfer_list	

		if(empty($transfer_list)){//Done with regular response transfer
			$sent_one = false;			
			foreach($notProcessed as &$tx){
				if($sent_one){
					break;
				}
				$settings = $this->processModel->getIAsettings($tx);	
				if($settings['scid'] == '' && $settings['ia_scid'] == ''){
					continue 1;
				}
				$transfer=[];
				$successful=false;
				if($settings !== false){					
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
				$payload_result = $this->deroApiModel->transfer([$sc_transfer]);
				$payload_result = json_decode($payload_result);
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
					$result = $this->processModel->markAsProcessed($tx['txid']);
						
					$given = new DateTime();
					$given->setTimezone(new DateTimeZone("UTC"));	
					$time_utc = $given->format("Y-m-d H:i:s");
					

					if($result !== false){
						$response = (object)[
						"incoming_id"=>$tx['id'],
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
						"time_utc"=>$time_utc
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
		$transfer['out_message'] =  "Integrated Address Inactive.";	
		$transfer['scid'] = "0000000000000000000000000000000000000000000000000000000000000000";
		$transfer_list[]=(object)$transfer;	
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
		$transfer['out_message'] =  "Integrated Address for S.C. Inactive.";	
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


