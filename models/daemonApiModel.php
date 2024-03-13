<?php 


class daemonApiModel extends App{  
	public $api_link;//node.derofoundation.org:11012/json_rpc (Default for Engram)

	public function __construct(){
        parent::__construct();
		
		$stmt=$this->pdo->prepare("SELECT * FROM settings WHERE name = 'daemon_api'");
		$stmt->execute([]);		
		if($stmt->rowCount()==0){
			return false;
		}
		$row = $stmt->fetch(PDO::FETCH_ASSOC);	
		$this->api_link = $row['value'];

	}



	function request($data){
		//remove formatting 
		$data = json_decode($data);
		$data = json_encode($data);	
		$errors = [];
		$response_data = false;
		
		$local = "127.0.0.1";  //url where this script runs
		

		
		$head = "POST /json_rpc HTTP/1.1\r\n"
				. "Upgrade: WebSocket\r\n"
				. "Connection: Upgrade\r\n"
				. "Origin: $local\r\n"
				. "Host: {$this->api_link}\r\n"
				. "Sec-WebSocket-Version: 13"."\r\n"
				. "Sec-WebSocket-Key: ".rand(0,999)."\r\n"
				. "Content-Type: application/json\r\n"
				. "Content-Length: " . strlen($data) . "\r\n"
				. "Connection: Close\r\n\r\n";
				 ;


		$sock = stream_socket_client("{$this->api_link}/ws",$error,$errnum,30,STREAM_CLIENT_CONNECT,stream_context_create(null));
		if (!$sock) {
			$errors[] = "[$errnum] $error";
		} else {
			fwrite($sock, $head . $data ) or $errors[] = 'error:'.$errno.':'.$errstr;
			$response = stream_get_contents($sock);
			
			if (preg_match('/Content-Length:\s*(\d+)/i', $response , $matches)) {
				$length = (int)$matches[1]; // Extract the length as an integer
			}

			$response_data = substr($response, -($length));
		}
	//$json = json_decode($response_data);
		return $response_data;
	}


	//Gets tx pool
	function getTxPool(){

		$data = '{
			"jsonrpc": "2.0",
			"id": "1",
			"method": "DERO.GetTxPool"
		}';

		return $this->request($data);

	}
	//Gets tx
	function getTX($tx_hash){

		$data = '{
			"jsonrpc": "2.0",
			"id": "1",
			"method": "DERO.GetTransaction",
			"params": {
				"txs_hashes": ["'.$tx_hash.'"]
			}
		}';

		return $this->request($data);

	}
		//Gets block at height
	function getBlockByHeight($height){

		$data = '{
			"jsonrpc": "2.0",
			"id": "1",
			"method": "DERO.GetBlock",
			"params": {
				"height": '. (int)$height .'
			}
		}';

		return $this->request($data);

	}
	
	
	
		//Gets block at height
	function nameToAddress($name){

		$data = '{
			"jsonrpc": "2.0",
			"id": "1",
			"method": "DERO.NameToAddress",
			"params": {
				"name": "'.$name.'",
				"topoheight": -1
			}
		}';

		return $this->request($data);

	}
	
	
}