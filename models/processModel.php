<?php 
class processModel extends App {  
	public $installed_time_utc='';

	function setInstalledTime(){

		$stmt=$this->pdo->prepare("SELECT value FROM settings WHERE name = 'install_time_utc'");
		$stmt->execute([]);		
		if($stmt->rowCount()==0){
			return false;
		}
		$row = $stmt->fetch(PDO::FETCH_ASSOC);		
		$this->installed_time_utc = $row['value'];
	}
	
	
	function nextCheckInTime(){
		
		$given = new DateTime();
		$given->setTimezone(new DateTimeZone("UTC"));
		$now_utc = $given->format("Y-m-d H:i:s");
	
	
		$stmt=$this->pdo->prepare("SELECT value FROM settings WHERE (name = 'next_checkin_utc' AND value < ?)");
		$stmt->execute([$now_utc]);		
		if($stmt->rowCount()==0){
			return false;
		}
		//past checkin time, save a new one and return the id for checkin api call
		$given->modify('+5 minutes');
		$next_checkin_utc = $given->format("Y-m-d H:i:s");
		
		
		$query='UPDATE settings SET 
			value=:value
			WHERE name=:name';	
		
		$stmt=$this->pdo->prepare($query);
		$stmt->execute(array(
			':value'=>$next_checkin_utc,
			':name'=>'next_checkin_utc'));				
					
		if($stmt->rowCount()==0){
			return false;
		}
		
		//see if registered, if not return false.
		$stmt=$this->pdo->prepare("SELECT value FROM settings WHERE name = 'web_api_id' AND NOT(value IS NULL OR value = '')");
		$stmt->execute([]);		
		if($stmt->rowCount()==0){
			return false;
		}
		
		return true;

	}	
	
	
	
	function saveAddress($address_array){
		
		$stmt=$this->pdo->prepare("SELECT id,ship_address FROM responses WHERE crc32 = ? ORDER BY id DESC LIMIT 1"); //AND (ship_address IS NULL OR ship_address = '')
		$stmt->execute([$address_array['id']]);		
		if($stmt->rowCount()==0){
			return false;
		}
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if($row['ship_address'] == $address_array['txid']){
			return false;
		}
		
		$query='UPDATE responses SET 
			ship_address=:ship_address
			WHERE crc32=:crc32 AND id=:id';	
		
		$stmt=$this->pdo->prepare($query);
		$stmt->execute(array(
			':ship_address'=>$address_array['txid'],
			':crc32'=>$address_array['id'],
			':id'=>$row['id']));				
					
		if($stmt->rowCount()==0){
			return false;
		}		
		
		
		return true;
	}
	
	
	function getAddressArray($entry){
		
		$address_string ='';
		//Find buyer address in payload
		foreach($entry->payload_rpc as $payload){
			if($payload->name == "C" && $payload->datatype == "S"){
				$address_string = $payload->value;
			}				
		}
		$given = new DateTime($entry->time);
		$given->setTimezone(new DateTimeZone("UTC"));	
		$time_utc = $given->format("Y-m-d H:i:s"); 
		
		if($address_string == '' || $time_utc < $this->installed_time_utc){ return false; }		
			
		$address_array = [];
		$address_parts = explode("?",$address_string);		
		foreach($address_parts as $part){
			$temp = explode("$",$part);
			$address_array[current($temp)]=end($temp);
		}
		$address_array['txid'] = $entry->txid;
		if(!isset($address_array['id'])){
			return false;
		}		
		return $address_array;
	}
	


	
	function txExists($tx){

		$stmt=$this->pdo->prepare("SELECT id FROM incoming WHERE txid = ?");
		$stmt->execute([$tx->txid]);		
		if($stmt->rowCount()==0){
			return false;
		}
		return true;
	}


	function updateInventory($tx){
		$stmt=$this->pdo->prepare("
		SELECT *,i_addresses.id AS ia_id FROM i_addresses 
		INNER JOIN products ON (i_addresses.product_id = products.id)
		WHERE i_addresses.port = ? AND i_addresses.ask_amount = ? AND i_addresses.status = '1'"
		);
		$stmt->execute([$tx->port,$tx->amount]);		
		if($stmt->rowCount()==0){
			return false;
		}
		$row = $stmt->fetch(PDO::FETCH_ASSOC);	
		$id='';
		$id_type='';
		if($row['inventory'] > 0){
			$query='UPDATE products SET inventory = inventory - 1
			WHERE id=:id';	
			$id=$row['id'];
			$id_type = 'p';
		}else if($row['ia_inventory'] > 0){
			$query='UPDATE i_addresses SET ia_inventory = ia_inventory - 1
			WHERE id=:id';	
			$id=$row['ia_id'];
			$id_type = 'ia';
		}
		if($id != ''){	//Still some inventory	
			$stmt=$this->pdo->prepare($query);
			$stmt->execute(array(':id'=>$id));		
			return ['id_type'=>$id_type,'p'=>$row['id'],'ia'=>$row['ia_id']];			
		}
		return false;
	}


	function insertNewTransaction($tx){
		
		if($tx === false || $this->txExists($tx) || $tx->time_utc < $this->installed_time_utc){// INSTALL_TIME_UTC|| $this->installed_time_utc ==''
			return false;
		}
		$p_and_ia_ids = $this->updateInventory($tx);
		//2024-01-23 22:22:43 in UTC
		$query='INSERT INTO incoming (
			txid,
			buyer_address,
			amount,
			port,
			for_product_id,
			for_ia_id,
			product_label,
			successful,
			processed,
			block_height,
			time_utc
			)
			VALUES
			(?,?,?,?,?,?,?,?,?,?,?)
			';	
		
		$array=array(
			$tx->txid,
			$tx->buyer_address,
			$tx->amount,
			$tx->port,
			$tx->for_product_id,
			($p_and_ia_ids ===false?null:$p_and_ia_ids['ia']),			
			$tx->product_label,
			($p_and_ia_ids ===false?0:1),
			0,
			$tx->height,
			$tx->time_utc,
			);				
				
		$stmt=$this->pdo->prepare($query);
		$stmt->execute($array);		
		if($stmt->rowCount()==0){
			return false;
		}
		return $p_and_ia_ids;
	}

	function makeTxObject($entry){
		
		$tx = [];	
		$tx['txid'] = $entry->txid;
		$tx['amount'] = $entry->amount;	
		$tx['height'] = $entry->height;
		
		$given = new DateTime($entry->time);
		$given->setTimezone(new DateTimeZone("UTC"));	
		$tx['time_utc'] = $given->format("Y-m-d H:i:s");
		
		$has_r = false;
		//Find buyer address in payload
		foreach($entry->payload_rpc as $payload){
			if($payload->name == "R" && $payload->datatype == "A"){
				$has_r = true;
				$tx['buyer_address'] = $payload->value;
			}else if($payload->name == "D" && $payload->datatype == "U"){
				$tx['port'] = $payload->value;					
			}					
		}
		//Not an integrated address
		if($has_r === false){
			return false;
		}
		
		$tx['for_product_id'] = 0;
		$tx['product_label'] = 'Inactive I.A.';
		//Determine product id and current label
		$ia_settings = $this->getIAsettings($tx);
		if($ia_settings!==false){
			$tx['for_product_id'] = $ia_settings['product_id'];
			$tx['product_label'] = $ia_settings['label'];
		}
		
		return (object)$tx;
	}


	//check responses to ensure they went through, if not mark as not processed 
	function unConfirmedTxs(){

		$stmt=$this->pdo->prepare("SELECT DISTINCT txid,txids,time_utc,t_block_height FROM responses WHERE confirmed = '0'");
		$stmt->execute([]);		
		if($stmt->rowCount()==0){
			return [];
		}
		
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	

	function markResAsConfirmed($txid){

		$query='UPDATE responses SET 
			confirmed=:confirmed
			WHERE txid=:txid';	
		
		$stmt=$this->pdo->prepare($query);
		$stmt->execute(array(
			':confirmed'=>1,
			':txid'=>$txid));				
					
		if($stmt->rowCount()==0){
			return false;
		}
	}
	function updateResponseTXID($txid,$newTXID){

		$query='UPDATE responses SET 
			txid=:txid1
			WHERE txid=:txid2';	
		
		$stmt=$this->pdo->prepare($query);
		$stmt->execute(array(
			':txid1'=>$newTXID,
			':txid2'=>$txid));				
					
		if($stmt->rowCount()==0){
			return false;
		}
	}


	function getTXCollection($txid){

		$stmt=$this->pdo->prepare("SELECT incoming_id FROM responses WHERE txid = ?");
		$stmt->execute([$txid]);		
		if($stmt->rowCount()==0){
			return [];
		}
		
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	function markIncAsNotProcessed($txid){
		$incoming_ids = $this->getTXCollection($txid);
		$ids =[];
		foreach($incoming_ids as $incoming_ids){
			$ids[] = $incoming_ids['incoming_id'];
		}
		
		$ids = implode(",",$ids);
		$result =$this->pdo->query("UPDATE incoming SET processed = '0' WHERE id IN($ids)");
		if($result !== false && $result->rowCount() > 0){		
			return true;
		}else{	
			return false;
		}
	}

	function markIncSuccessfulTwo($inc_id){
		//For failed token transfers (error of not enough etc)
		$result =$this->pdo->query("UPDATE incoming SET successful = '2' WHERE id ='$inc_id'");
		if($result !== false && $result->rowCount() > 0){		
			return true;
		}else{	
			return false;
		}
	}




	function getConfirmedInc($txid){

		$stmt=$this->pdo->prepare("
		SELECT *, responses.out_message AS response_out_message FROM responses 
		INNER JOIN incoming ON responses.incoming_id = incoming.id 
		RIGHT JOIN products ON incoming.for_product_id = products.id 
		WHERE responses.txid =  ?
		");
		
		$stmt->execute([$txid]);		
		if($stmt->rowCount()==0){
			return [];
		}
		
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}



	function unprocessedTxs(){

		$stmt=$this->pdo->prepare("SELECT * FROM incoming WHERE processed = '0'");
		$stmt->execute([]);		
		if($stmt->rowCount()==0){
			return false;
		}
		
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

		
	function getIAsettings($tx,$addon=''){

		$stmt=$this->pdo->prepare("
		SELECT *, i_addresses.comment AS ia_comment,i_addresses.id AS ia_id FROM i_addresses 
		INNER JOIN products ON i_addresses.product_id = products.id  
		WHERE i_addresses.ask_amount = ? AND i_addresses.port = ? AND i_addresses.status = '1' $addon");
		$stmt->execute([$tx['amount'],$tx['port']]);		
		if($stmt->rowCount()==0){
			return false;
		}
		
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	function markAsProcessed($txid){

		$query='UPDATE incoming SET 
			processed=:processed
			WHERE txid=:txid';	
		
		$stmt=$this->pdo->prepare($query);
		$stmt->execute(array(
			':processed'=>1,
			':txid'=>$txid));				
					
		if($stmt->rowCount()==0){
			return false;
		}
	}



	function getRespsonseTXIDS($incoming_id){

		$stmt=$this->pdo->prepare("SELECT txids FROM responses WHERE incoming_id = ?");
		$stmt->execute([$incoming_id]);		
		if($stmt->rowCount()==0){
			return false;
		}
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row['txids'];
	}

	function updateResponseTX($response){
		
		
		//Add to list of txns
		$responseTXIDS = $this->getRespsonseTXIDS($response->incoming_id);		
		$responseTXIDS = explode(",",$responseTXIDS);		
		$responseTXIDS[] = $response->txid;
		$responseTXIDS = implode(",",$responseTXIDS);
		
		
		$query='UPDATE responses SET 
			txid=:txid,
			txids=:txids,
			time_utc=:time_utc,
			t_block_height=:t_block_height
			WHERE incoming_id=:incoming_id';	
		
		$stmt=$this->pdo->prepare($query);
		$stmt->execute(array(
			':txid'=>$response->txid,
			':txids'=>$responseTXIDS,
			':time_utc'=>$response->time_utc,
			':t_block_height'=>$response->t_block_height,
			':incoming_id'=>$response->incoming_id));				
					
		if($stmt->rowCount()==0){
			return false;
		}
	}

	function checkForResponseById($incoming_id){

		$stmt=$this->pdo->prepare("SELECT * FROM responses WHERE incoming_id = ? AND confirmed = '0'");
		$stmt->execute([$incoming_id]);		
		if($stmt->rowCount()==0){
			return false;
		}
		
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}


	function saveResponse($response){
		//See if a response record exists, if so just update the txid (this way only the original uuid is used)
		$responseRecord = $this->checkForResponseById($response->incoming_id);
		if($responseRecord !== false){
			$this->updateResponseTX($response);
			return true;
		}
		
		//No record, insert one.
		$query='INSERT INTO responses (
			incoming_id,
			txid,
			txids,
			type,
			buyer_address,
			out_amount,
			port,
			out_message,
			out_message_uuid,
			uuid,
			api_url,
			out_scid,
			crc32,
			time_utc,
			t_block_height
			)
			VALUES
			(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
			';	
		
		$array=array(
			$response->incoming_id,
			$response->txid,
			$response->txid,
			$response->type,
			$response->buyer_address,
			$response->out_amount,
			$response->port,
			$response->out_message,
			$response->out_message_uuid,
			$response->uuid,
			$response->api_url,
			$response->out_scid,
			$response->crc32,
			$response->time_utc,
			$response->t_block_height
			);				
				
		$stmt=$this->pdo->prepare($query);
		$stmt->execute($array);		
		if($stmt->rowCount()==0){
			return false;
		}
		return true;
	}
	/*function removeResponse($txid){

		$stmt=$this->pdo->prepare("DELETE FROM responses WHERE txid = ?");
		$stmt->execute([$txid]);		
		if($stmt->rowCount()==0){
			return [];
		}
		// fix maybe lol
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}*/
}

