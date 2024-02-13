<?php 


class deroApiModel extends App{  
	public $ip;//127.0.0.1:10103 (for Engram cyberdeck)
	public $port;
	public $user;
	public $pass;
	//public $scid="0000000000000000000000000000000000000000000000000000000000000000";

	public function __construct(){
        parent::__construct();
		
		$stmt=$this->pdo->prepare("SELECT * FROM settings WHERE NOT(name = 'install_time_utc')");
		$stmt->execute([]);		
		if($stmt->rowCount()==0){
			return false;
		}
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);	
		$settings=[];
		foreach($rows as $row){
			$settings[$row['name']] = $row['value'];
		}
		$this->ip = $settings['dero_api_ip'];
		$this->port = $settings['dero_api_port'];
		$this->user = $settings['dero_api_user'];
		$this->pass = $settings['dero_api_pass'];
	}



	function connectionErrors($ch){
		// Check HTTP status code
		if (!curl_errno($ch)) {
		  switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
			case 200:  # OK
			  break;
			default:
			  return 'Unexpected HTTP code: '. $http_code ;
		  }
		}else{
			return curl_error($ch) . ' ' . curl_errno($ch);
		}
	}


	//API funtions

	//Gets the list of incoming transfers
	function getTransferByTXID($transaction_id){

	$data = '{
		"jsonrpc": "2.0",
		"id": "1",
		"method": "GetTransferbyTXID",
		"params": {
			"txid": "'.$transaction_id.'"
		}
	}';

	$json = json_decode($data,true);
	$json = json_encode($json);

		$ch = curl_init("http://{$this->ip}:{$this->port}/json_rpc");
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$json);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [ 		
			"Authorization: Basic " . base64_encode($this->user.':'.$this->pass),
			"Content-Type: application/json"
		]);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		$output = curl_exec($ch);
		
		$this->connectionErrors($ch);

		curl_close($ch);

		return $output;

	}




	//Creates a new integrated address
	//When used as a send to address it will display the in message and fill in the correct amounts as well as allowing a port to be defined 
	function makeIntegratedAddress($d_port,$in_message,$ask_amount){
		$data = '{
			"jsonrpc": "2.0",
			"id": "1",
			"method": "MakeIntegratedAddress",
			"params": {
			  "payload_rpc": [
				{
				  "name": "C",
				  "datatype": "S",
				  "value": "'.$in_message.'"
				},
				{
				  "name": "D",
				  "datatype": "U",
				  "value": '.$d_port.'
				},
				{
				  "name": "N",
				  "datatype": "U",
				  "value": 0
				},
				{
				  "name": "V",
				  "datatype": "U",
				  "value": '.$ask_amount.'
				}
			  ]
			}
		}';

	$json = json_decode($data,true);
	$json = json_encode($json);

		$ch = curl_init("http://{$this->ip}:{$this->port}/json_rpc");
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$json);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [ 		
			"Authorization: Basic " . base64_encode($this->user.':'.$this->pass),
			"Content-Type: application/json"
		]);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		$output = curl_exec($ch);
		
		$this->connectionErrors($ch);
		
		curl_close($ch);

		return $output;

	}

	//Gets the list of incoming transfers
	function getTransfers(){
		$data = '{
			"jsonrpc": "2.0",
			"id": "1",
			"method": "GetTransfers",
			"params": {
			  "out": false,
			  "in": true
			}
		}';

	$json = json_decode($data,true);
	$json = json_encode($json);

		$ch = curl_init("http://{$this->ip}:{$this->port}/json_rpc");
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$json);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [ 		
			"Authorization: Basic " . base64_encode($this->user.':'.$this->pass),
			"Content-Type: application/json"
		]);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		$output = curl_exec($ch);
		
		$this->connectionErrors($ch);

		curl_close($ch);

		return $output;

	}
	
	function createTransferObjectString($transfer){	
		return '
		{
			"scid": "'.$transfer->scid.'",
			"destination": "'.$transfer->address.'",
			"amount": '.$transfer->respond_amount.',
			"payload_rpc":
			[
				{
				"name": "C",
				"datatype": "S",
				"value": "'.$transfer->out_message.'"
				}
			]
		}';		
	}
	
	/*********************************************************************/
	/* Creates a transfer to respond to new sales (destination address). */ 
	/* Transfers can transfer a SCID (not setup for that currently).     */
	/* 128 bytes max for the out message (link or uuid etc).             */
	/* Amount should be at least .00001 dero or 1 deri.                  */
	/*********************************************************************/
	function transfer($transfers=[]){	
		$transfer_string='';
		foreach($transfers as $transfer){
			$transfer_string .= $this->createTransferObjectString($transfer).",";
		}
		$transfer_string = rtrim($transfer_string, ",");
		
		$data = '{
		"jsonrpc": "2.0",
		"id": "1",
		"method": "transfer",
		"params": {
		   "ringsize": 16,
		   "transfers":
		   [
			'.$transfer_string.'
		  ]
		}
	  }';

	$json = json_decode($data,true);
	$json = json_encode($json);

		$ch = curl_init("http://{$this->ip}:{$this->port}/json_rpc");
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$json);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [ 		
			"Authorization: Basic " . base64_encode($this->user.':'.$this->pass),
			"Content-Type: application/json"
		]);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		$output = curl_exec($ch);	
		
		$this->connectionErrors($ch);

		return $output;

	}

}

