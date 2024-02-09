<?php 
class Initialize extends App {  	
	function getProducts(){
		require_once('system/dbtablesetup.php');
		$this->loadModel("productModel");

		$product_results = $this->productModel->getProductsList();		
		
		foreach ($product_results as &$product){
			$product['iaddress'] = $this->productModel->getIAddresses($product['id']);			
		}		
		return ["products"=>$product_results];		
	}
}