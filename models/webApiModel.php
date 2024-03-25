<?php

class webApiModel extends App{  
	public $api_url="";
	public $user="";
	public $wallet="";
	public $id="";	


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
		
		$this->api_url = $settings['web_api_url'];
		$this->user = $settings['web_api_user'];
		$this->wallet = $settings['web_api_wallet'];
		$this->id = $settings['web_api_id'];

	}
	
	
	

	function tryPending(){
		$stmt=$this->pdo->prepare("
		SELECT * FROM pending 
		WHERE id IN (SELECT MAX(id) FROM pending GROUP BY url,method,aid)
		");
		$stmt->execute(array());
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);		
		foreach($rows as $row){
			//Get rid of the old ones, if it fails then it will be added back.
			$this->deleteRequests($row['method'],$row['aid']);
			//retry the latest.
			$this->retry($row);
		}
	}
	
	
	function retry($row){
		$url = $row['url'];
		$json = $row['json_text'];

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$json);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [ 		
			"Authorization: Basic " . base64_encode($this->user.':'.$this->id),
			"Content-Type: application/json"
		]);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		$output = curl_exec($ch);
		
		$error = $this->connectionErrors($ch);

		curl_close($ch);
		

		if($output!=''){
			$jresult = json_decode($output);
			if($jresult->success != true && $error ==''){
				$error = 'API Error';
			}
		}else{
			$error = 'No Response';
		}
		$this->logRequest($url,$json,$error,$row['method'],$row['aid']);

		return $output;

	}
	
	function deleteRequests($method,$applicable_id){
		if($method == 'newTX'){return}
		$stmt=$this->pdo->prepare("DELETE FROM pending WHERE method = ? AND aid=?");
		$stmt->execute([$method,$applicable_id]);	
	}
	function logRequest($url,$jsontxt,$error,$method,$applicable_id){
		//make sure seller has an account setup
		if($this->id==''){return false;}
		//Save request and error
		if($error !=''){
			$query='INSERT INTO pending (
				url,
				json_text,
				method,
				aid,
				error
				)
				VALUES
				(?,?,?,?,?)
				';	
			
			$array=array(
				$url,
				$jsontxt,
				$method,					
				$applicable_id,
				$error	
				);				
					
			$stmt=$this->pdo->prepare($query);
			$stmt->execute($array);		
			if($stmt->rowCount()==0){
				return false;
			}
			return true;
		}
		//If successful remove any older failed requests for same product or I.A.
		$this->deleteRequests($method,$applicable_id);		
		
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
	//Sends to your website
	function register($settings){
		//$url = $tx['out_message'];
		$data = '{
			"method": "register",
			"params": {
				"username": "'.$this->user.'",
				"wallet": "'.$this->wallet.'"
			}
		}';

		$json = json_decode($data,true);
		$json = json_encode($json);

		$ch = curl_init($this->api_url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$json);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [ 		
			"Authorization: Basic " . base64_encode($this->user.':'.$this->id),
			"Content-Type: application/json"
		]);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		$output = curl_exec($ch);
		
		$error = $this->connectionErrors($ch);
//var_dump($output);
		curl_close($ch);
		
		$jresult = json_decode($output);
		
		
		
		if($output!='' && $jresult != ''){
			
			if($jresult->success != true && $error ==''){
				$error = 'API Error';
			}else if($jresult->success){
				return $jresult->reg;
			}
		}else{
			$error = 'No Response';
		}

		return false;

	}

	
	
	//Sends new transaction to a website when uuid is selected and the out_message contains the url (as of now...)
	function newTX($tx){
		$url = $tx['api_url'];
		if(!filter_var($url, FILTER_VALIDATE_URL)){
			return false;
		}
		$data = '{
			"method": "newTX",
			"params": {
				"uuid": "'.$tx['uuid'].'",
				"ia_id": '.(int)$tx['for_ia_id'].'
			}
		}';

		$json = json_decode($data,true);
		$json = json_encode($json);

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$json);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [ 		
			"Authorization: Basic " . base64_encode($this->user.':'.$this->id),
			"Content-Type: application/json"
		]);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		$output = curl_exec($ch);
		
		$error = $this->connectionErrors($ch);

		curl_close($ch);
		
		$jresult = json_decode($output);
		if($output!='' && $jresult != ''){
			
			if($jresult->success != true && $error ==''){
				$error = 'API Error';
			}
		}else{
			$error = 'No Response';
		}
		
		//newTXs are not deleted (will need to be updated if name changes)
		$this->logRequest($url,$json,$error,'newTX','');
		
		return $output;

	}
	


	//Sends a product to a website
	function submitProduct($product,$new_image=true){
	
			
		$data = [];	
		$data["method"] = "submitProduct";
		
		$params=[];
		$params["id"] = $product['id'];
		$params["p_type"] = $product['p_type'];
		$params["label"] = $product['label'];
		$params["details"] = $product['details'];
		$params["scid"] = $product['scid'];
		$params["inventory"] = $product['inventory'];
		
		if($new_image){
			$params["image"] = $product['image'];
		}		
		
		$data["params"] = (object)$params;
		
		$json = json_encode($data);

		$ch = curl_init($this->api_url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$json);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [ 		
			"Authorization: Basic " . base64_encode($this->user.':'.$this->id),
			"Content-Type: application/json"
		]);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		$output = curl_exec($ch);
		
		$error = $this->connectionErrors($ch);

		curl_close($ch);
		$jresult = json_decode($output);
		if($output!='' && $jresult != ''){
			if($jresult->success != true && $error ==''){
				$error = 'API Error';
			}
		}else{
			$error = 'No Response';
		}
		$this->logRequest($this->api_url,$json,$error,'submitProduct',$product['id']);
		
		return $output;

	}
	
	//Deletes a product from website
	function deleteProduct($product_id){
	
			
		$data = [];	
		$data["method"] = "submitProduct";
		
		$params=[];
		$params["id"] = (int)$product_id;
		$params["action"] = 'delete';
		
		$data["params"] = (object)$params;
		
		$json = json_encode($data);

		$ch = curl_init($this->api_url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$json);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [ 		
			"Authorization: Basic " . base64_encode($this->user.':'.$this->id),
			"Content-Type: application/json"
		]);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		$output = curl_exec($ch);
		
		$error = $this->connectionErrors($ch);

		curl_close($ch);
		$jresult = json_decode($output);
		if($output!='' && $jresult != ''){
			if($jresult->success != true && $error ==''){
				$error = 'API Error';
			}
		}else{
			$error = 'No Response';
		}
		$this->logRequest($this->api_url,$json,$error,'submitProduct',$product_id);
		
		return $output;

	}
	
	
	
	function submitIAddress($i_address){
		
		$data = [];	
		$data["method"] = "submitIAddress";
		//this is goofy... but tested lols
		$params=[];
		$params["id"] = $i_address['id'];
		$params["product_id"] = $i_address['product_id'];
		$params["iaddr"] = $i_address['iaddr'];
		$params["ask_amount"] = $i_address['ask_amount'];
		$params["comment"] = $i_address['comment'];	
		$params["ia_scid"] = $i_address['ia_scid'];		
		$params["status"] = $i_address['status'];		
		$params["ia_inventory"] = $i_address['ia_inventory'];				
		
		$data["params"] = (object)$params;

		$json = json_encode($data);

		$ch = curl_init($this->api_url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$json);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [ 		
			"Authorization: Basic " . base64_encode($this->user.':'.$this->id),
			"Content-Type: application/json"
		]);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		$output = curl_exec($ch);
		
		$error = $this->connectionErrors($ch);

		curl_close($ch);
		
		
		$jresult = json_decode($output);
		if($output!='' && $jresult != ''){
			
			if($jresult->success != true && $error ==''){
				$error = 'API Error';
			}
		}else{
			$error = 'No Response';
		}
		$this->logRequest($this->api_url,$json,$error,'submitIAddress',$i_address['id']);

		return $output;

	}	
	
	
	function deleteIAddress($i_address_id){
		
		$data = [];	
		$data["method"] = "submitIAddress";
		//this is goofy... but tested lols
		$params=[];
		$params["id"] = (int)$i_address_id;	
		$params["action"] = 'delete';	
		
		$data["params"] = (object)$params;

		$json = json_encode($data);

		$ch = curl_init($this->api_url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$json);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [ 		
			"Authorization: Basic " . base64_encode($this->user.':'.$this->id),
			"Content-Type: application/json"
		]);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		$output = curl_exec($ch);
		
		$error = $this->connectionErrors($ch);

		curl_close($ch);
		
		
		$jresult = json_decode($output);
		if($output!='' && $jresult != ''){
			
			if($jresult->success != true && $error ==''){
				$error = 'API Error';
			}
		}else{
			$error = 'No Response';
		}
		$this->logRequest($this->api_url,$json,$error,'submitIAddress',$i_address_id);

		return $output;

	}		
	
	
	
	
	function checkIn(){
	
		$data = '{
			"method": "checkIn"
		}';

		$json = json_decode($data,true);
		$json = json_encode($json);

		$ch = curl_init($this->api_url);
		
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($ch, CURLOPT_POSTFIELDS,$json);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [ 		
			"Authorization: Basic " . base64_encode($this->user.':'.$this->id),
			"Content-Type: application/json"
		]);

	//	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_exec($ch);
		
		//$error = $this->connectionErrors($ch);

		curl_close($ch);
		
		//$this->logRequest($this->api_url,$json,$error,'checkIn','');

		return true;

	}	
	
	
	
	
}
