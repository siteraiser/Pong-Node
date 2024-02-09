<?php 
class Loadout extends App {  	
	function load(){
		
		$this->loadModel('loadoutModel');
		$this->loadModel("deroApiModel");
		$this->loadoutModel->setInstalledTime();
		
		$transactions = $this->loadoutModel->getTransactionList();		

		$export_transfers_result = $this->deroApiModel->getTransfers();
		$export_transfers_result = json_decode($export_transfers_result);
		if($export_transfers_result === NULL){
			$errors[] = "Wallet Connection Error.";
		}else{
			
			foreach($export_transfers_result->result->entries as $entry){		
				//See if there is a payload... todo: check if it is a shipping address submission
				if(isset($entry->payload_rpc)){
					$given = new DateTime($entry->time);
					$given->setTimezone(new DateTimeZone("UTC"));	
					$time_utc = $given->format("Y-m-d H:i:s");
					$qualifies = true;
					if($time_utc < $this->loadoutModel->installed_time_utc){
						$qualifies = false;
					}
					$address_string = '';
					//Find buyer address in payload
					foreach($entry->payload_rpc as $payload){
						if($payload->name == "R" && $payload->datatype == "A"){
							$qualifies = false;			
						}else if($payload->name == "C" && $payload->datatype == "S"){
							$address_string = $payload->value;
						}					
					}
					
					//Not an integrated address
					if($qualifies && $address_string !=''){						
						foreach($transactions as &$transaction){
							if($transaction['ship_address'] != ''){
								if($transaction['ship_address'] == $entry->txid){
									$address_array = [];
									$address_parts = explode("?",$address_string);		
									foreach($address_parts as $part){
										$temp = explode("$",$part);
										$address_array[current($temp)]=end($temp);
									}
									$ship_address = $address_array['n'].PHP_EOL;
									$ship_address .= $address_array['l1'].PHP_EOL;
									$ship_address .= $address_array['l2'].PHP_EOL;
									$ship_address .= $address_array['c1'].PHP_EOL;
									$ship_address .= $address_array['s'].PHP_EOL;
									$ship_address .= $address_array['z'].PHP_EOL;
									$ship_address .= $address_array['c2'].PHP_EOL;			
									
									$transaction['ship_address'] = $ship_address;
									
								}								
							}							
						}						
					}					
				}	
			}
		}
		return ["transactions"=>$transactions];		
	}
}