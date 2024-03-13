<?php 


class walletApiModel extends App{  
	public $ip;//127.0.0.1:10103 (for Engram cyberdeck)
	public $port;
	public $user;
	public $pass;

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
		$this->ip = $settings['wallet_api_ip'];
		$this->port = $settings['wallet_api_port'];
		$this->user = $settings['wallet_api_user'];
		$this->pass = $settings['wallet_api_pass'];
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




	//Gets wallet address
	function getAddress(){

		$data = '{
		"jsonrpc": "2.0",
		"id": "1",
		"method": "GetAddress"
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
	
	
	//Gets wallet balance
	function getBalance(){

		$data = '{
		"jsonrpc": "2.0",
		"id": "1",
		"method": "GetBalance"
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
	

	
	//Gets the block height
	function getHeight(){

		$data = '{
		"jsonrpc": "2.0",
		"id": "1",
		"method": "GetHeight"
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
		$data = [];
		$data["jsonrpc"] = "2.0";
		$data["id"] = "1";
		$data["method"] = "MakeIntegratedAddress";
		
		
		$payload_rpc = [];
		$payload_rpc[] = 
			(object)[
			"name"=>"C",
			"datatype"=>"S",
			"value"=>$in_message
			];
		$payload_rpc[] = 
			(object)[
			"name"=>"D",
			"datatype"=>"U",
			"value"=>(int)$d_port
			];
		$payload_rpc[] = 
			(object)[
			"name"=>"N",
			"datatype"=>"U",
			"value"=>0
			];
		$payload_rpc[] = 
			(object)[
			"name"=>"V",
			"datatype"=>"U",
			"value"=>(int)$ask_amount
			];
			
		$params = [];
		$params["payload_rpc"] = $payload_rpc;
		
		
		$data["params"] = (object)$params;
		

		$json = json_encode($data);

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
	function getAllTransfers($min_height=false){
		
		$data = '{
			"jsonrpc": "2.0",
			"id": "1",
			"method": "GetTransfers",
			"params": {
			  "out": true,
			  "in": true'.($min_height !== false ? ', "min_height":'.(int)$min_height:'').'	
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
	function getInTransfers($min_height=false){
		
		$data = '{
			"jsonrpc": "2.0",
			"id": "1",
			"method": "GetTransfers",
			"params": {
			  "out": false,
			  "in": true'.($min_height !== false ? ', "min_height":'.(int)$min_height:'').'			  
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


	function createTransferObject($transfer){	
		$t = [];
		$t["scid"] = $transfer->scid;
		$t["destination"] = $transfer->address;
		$t["amount"] = (int)$transfer->respond_amount;

		$payload_rpc = [];
		$payload_rpc[] = 
			(object)[
			"name"=>"C",
			"datatype"=>"S",
			"value"=>$transfer->out_message
			];
		$t["payload_rpc"] = $payload_rpc;
		return $t;		
	}
	/*********************************************************************/
	/* Creates a transfer to respond to new sales (destination address). */ 
	/* Transfers can transfer a SCID (not setup for that currently).     */
	/* 128 bytes max for the out message (link or uuid etc).             */
	/* Amount should be at least .00001 dero or 1 deri.                  */
	/*********************************************************************/
	function transfer($transfers=[]){	
	
		$transfers_array = [];
		foreach($transfers as $transfer){
			$transfers_array[] = (object)$this->createTransferObject($transfer);
		}

		$data = [];
		$data["jsonrpc"] = "2.0";
		$data["id"] = "1";
		$data["method"] = "transfer";
		
		$params = [];
		$params["ringsize"] = 8;
		$params["transfers"] = $transfers_array;
		
		$data["params"] = (object)$params;
	

	$json = json_encode($data);

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



	function transferOwnership($transfer){	

		$data = [];
		$data["jsonrpc"] = "2.0";
		$data["id"] = "0";
		$data["method"] = "transfer";
		//$data["amount"] = 2;
		$params = [];
		$params["ringsize"] = 2;	
		
		
		$payload_rpc = [];
		$payload_rpc[] = 
			(object)[
			"name"=>"C",
			"datatype"=>"S",
			"value"=>$transfer->out_message
			];
		
		$transfers_array[] = 
			(object)[
			"amount"=>(int)$transfer->respond_amount,
			"destination"=>$transfer->address,
			"payload_rpc"=>$payload_rpc
			];
	
		$params["transfers"] = $transfers_array;
		
		$params["scid"] = $transfer->scid;
	
		$sc_rpc_array[] = 
			(object)[
			"name"=>"entrypoint",
			"datatype"=>"S",
			"value"=>'TransferOwnership'
			];
		$sc_rpc_array[] = 
			(object)[
			"name"=>"newowner",
			"datatype"=>"S",
			"value"=>$transfer->address
			];
		$params["sc_rpc"] = $sc_rpc_array;
		
		$data["params"] = (object)$params;
		

	$json = json_encode($data);

		$ch = curl_init("http://127.0.0.1:10103/json_rpc");
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$json);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [ 		
			"Authorization: Basic " . base64_encode('secret:pass'),
			"Content-Type: application/json"
		]);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		$output = curl_exec($ch);	
		curl_close($ch);
		$this->connectionErrors($ch);

		return $output;

	}
/**/

}

