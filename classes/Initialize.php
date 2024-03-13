<?php 
class Initialize extends App {  	
	function getProducts(){
		$errors =[];
		require_once('system/dbtablesetup.php');
		$this->loadModel("productModel");
		$this->loadModel('settingsModel');
		$this->loadModel('walletApiModel');
		$this->loadModel('daemonApiModel');
		
		$result = json_decode($this->daemonApiModel->getBlockByHeight(420));
		
		if(isset($result->error->message)){
			$errors[] ="Node ({$this->daemonApiModel->api_link}) is not a full node. ".$result->error->message;
			//disable products...
		}	

	
		$product_results = $this->productModel->getProductsList();		
		
		foreach ($product_results as &$product){
			$product['iaddress'] = $this->productModel->getIAddresses($product['id']);			
		}		
		
		
		//get the apilink
		$settings = $this->settingsModel->getSettings();
		$api_url = '';
		foreach($settings as $setting){
			//Get wallet address if it hasn't been changed yet.
			if($setting['name']=='web_api_url'){
				$api_url = $setting['value'];
			}
		}

		return ["api_url"=>$api_url,"errors"=>$errors,"products"=>$product_results];		
	}
}
