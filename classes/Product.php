<?php 
class Product extends App {  

	function add(){
		$this->loadModel("productModel");
		$this->loadModel("addProductModel");
		$this->loadModel("deroApiModel");
		$this->loadModel("webApiModel");
		$errors=[];





		if(!empty($_POST)){
			if($_POST['comment']=='' || $_POST['ask_amount'] == '' || $_POST['ask_amount'] < 1 || $_POST['port'] ==  '' ){		
				if($_POST['ask_amount'] < 1){
					$errors[] = "Minumum Ask Amount is 1";
				}else{
					$errors[] = "Required fields missing";
				}
			}
			//Generate integrated address
			if(empty($errors)){	
				$export_address_result = json_decode($this->deroApiModel->makeIntegratedAddress($_POST['port'],$_POST['comment'],$_POST['ask_amount']));
				if($export_address_result ==''){			
					$errors[] = "Couldn't generate integrated address";
				}
			}
			//See if integrated address exists
			if(empty($errors)){
				$comment = $this->addProductModel->integratedAddressExists($export_address_result->result->integrated_address);
				if($comment !== false){
					$errors[] = "Integrated address already exists for \"$comment\". Change comment, ask amount or port.";
				}
			}
			//Check if port is being used with same price
			if(empty($errors)){
				$result = $this->addProductModel->portExists($_POST['port'],$_POST['ask_amount']);
				if($result !== false){
				$errors[] = "Port already exists for \"{$result['comment']}\" with ask amount {$_POST['ask_amount']} and active integrated address: ".$result['iaddr'];
				}
			}
			//Save Product
			if(empty($errors)){			
				$product_id = $this->addProductModel->insertProduct();
				//Send update to website... 
				$this->webApiModel->submitProduct($this->productModel->getProductById($product_id));

				if($product_id === false){
					$errors[] = "Failed to add product to db";
				}	
			}

			//Save integrated address with product id
			if(empty($errors)){		
				$ia_id = $this->addProductModel->insertIntegratedAddress($product_id,$export_address_result->result->integrated_address);
				//Send to website...
				$this->webApiModel->submitIAddress($this->productModel->getIAddressById($ia_id));
				if($ia_id  === false){
					$errors[] = "Failed to add integrated address to db";
				}
			}
			
		}

		if(empty($errors)){
			$product_result = $this->productModel->getProductById($product_id);
			$product_result['iaddress'] = $this->productModel->getIAddresses($product_result['id']);
			return ["success"=>true,"product"=>$product_result];
		}else{	
			return ["success"=>false,"errors"=>$errors];
		}
	}
	
	
	
	function edit(){
		$this->loadModel("productModel");
		$this->loadModel("editProductModel");
		$this->loadModel("deroApiModel");
		$this->loadModel("webApiModel");

		

		
		$errors=[];
/*		echo strlen($_POST['image']);
		
echo'<pre>';
var_dump($_POST['image']);
echo'</pre>';
*/
		if(!empty($_POST)){			
			if($_POST['comment']=='' || $_POST['ask_amount'] ==  '' || $_POST['ask_amount'] < 1 || $_POST['port'] ==  '' ){		
				if($_POST['ask_amount'] < 1){
					$errors[] = "Minumum Ask Amount is 1";
				}else{
					$errors[] = "Required fields missing";
				}
			}			
			$ia = '';
			$same_ia = $this->editProductModel->sameIntegratedAddress();//no changes to comment, amount or port and is same product id (no need to generate a new one...)
			//Generate integrated address
			if(empty($errors)){	
				
				$export_address_result = json_decode($this->deroApiModel->makeIntegratedAddress($_POST['port'],$_POST['comment'],$_POST['ask_amount']));
				if($export_address_result ==''){			
					$errors[] = "Couldn't generate integrated address";
				}else{
					$ia = $export_address_result->result->integrated_address;
				}
			}			

			//See if integrated address exists 
			if(empty($errors) && !$same_ia){
				$result = $this->editProductModel->integratedAddressExists($ia);
				if($result !== false){
					$errors[] = "Integrated address already exists for \"{$result['comment']}\". Change comment, ask amount or port.";
				}
			}
			//Check if port is being used with same price
			if(empty($errors) && !$same_ia){
				$result = $this->editProductModel->portExists($_POST['port'],$_POST['ask_amount']);
				if($result !== false){
					$errors[] = "Port already exists for \"{$result['comment']}\" with ask amount {$_POST['ask_amount']} and active integrated address: ".$result['iaddr'];
				}
			}
			
			$failed_ia_ids = [];
			//Save Product
			if(empty($errors)){	
				//handle the iaddress status checkboxes
				
				$active = [];
				if(isset($_POST['iaddress_status'])){
					
					foreach($_POST['iaddress_status'] as $key => $value){
						$active[] = $key;		
					}		
				}	
					
				$iadds = $this->productModel->getIAddresses($_POST['pid']);

				foreach($iadds as $iaddr){
					$changes=[];
					//Handle status updates
					if(in_array($iaddr['id'],$active)){
						//don't allow active ia for more than one product
						 if($iaddr['status'] == 0){
							$res = $this->editProductModel->integratedAddressExistsElsewhere($iaddr);
							if($res !== false){
								$failed_ia_ids[] = $iaddr['id'];
								$errors[] = "Integrated address already exists for \"{$res['comment']}\", and can only be used for one product at a time. Ask amount {$res['ask_amount']} and active integrated address: {$res['iaddr']} ";
							}else{
								$this->editProductModel->toggleIAddr($iaddr['id'],1);
								$changes[] = true;
								
							}
						}
					}else{//not submitted as active but is then deactivate
						if($iaddr['status'] == 1){
							$this->editProductModel->toggleIAddr($iaddr['id'],0);	
							$changes[] = true;		
						}
					}
					//Handle inventory updates
					if($this->editProductModel->setInventory($iaddr['id']) !== false){
						$changes[] = true;
					}
					
					//Smart Contract updates
					if($this->editProductModel->setSCID($iaddr['id']) !== false){
						$changes[] = true;
					}
					
					//Smart Contract updates
					if($this->editProductModel->setIARespondAmount($iaddr['id']) !== false){
						//$changes[] = true;
					}
					
					if(in_array(true,$changes)){
						//update when changed
						$this->webApiModel->submitIAddress($this->productModel->getIAddressById($iaddr['id']));
					}
				}	
				
				
				
				
				$product_update = $this->editProductModel->updateProduct();

				//Send update to website... ;
				$this->webApiModel->submitProduct($this->productModel->getProductById($product_update['id']),$product_update['new_image']);
			}

			//Save integrated address with product id
			if(empty($errors) && !$same_ia){		
				$ia_id = $this->editProductModel->insertIntegratedAddress($product_update['id'],$export_address_result->result->integrated_address);
				$this->webApiModel->submitIAddress($this->productModel->getIAddressById($ia_id));
				//Send to website... $this->editProductModel->getProductById($product_id);
				if($ia_id  === false){
					$errors[] = "Failed to add integrated address to db";
				}
			}
			
		}

		if(empty($errors)){
			$product_results = $this->productModel->getProductsList();
			foreach ($product_results as &$product){
				$product['iaddress'] = $this->productModel->getIAddresses($product['id']);		
			}
			return ["success"=>true,"products"=>$product_results];
		}else{	
			return ["success"=>false,"errors"=>$errors,"failed_ia_ids"=>$failed_ia_ids];
		}
	}
	
	
	
	function deleteIntegratedAddress($id){
		$this->loadModel("deleteProductModel");
		$this->loadModel("productModel");
		$this->loadModel("webApiModel");		
		$product_id = $this->deleteProductModel->deleteIntegratedAddress($id);
		if(is_array($product_id)){
			$errors = $product_id;
		}
		
		if(empty($errors)){
			$this->webApiModel->deleteIAddress($i_address);
			
			$product_results = $this->productModel->getProductsList();
			foreach ($product_results as &$product){
				$product['iaddress'] = $this->productModel->getIAddresses($product['id']);		
			}
			return ["success"=>true,"products"=>$product_results];
		}else{	
			return ["success"=>false,"errors"=>$errors];
		}
	}
	
	function deleteProduct($product_id){
		$this->loadModel("deleteProductModel");
		$this->loadModel("productModel");
		$this->loadModel("webApiModel");
		 
		
		$errors = $this->deleteProductModel->deleteProduct($product_id);
		
		if(empty($errors)){
			$this->webApiModel->deleteProduct($product_id);
			$product_results = $this->productModel->getProductsList();
			foreach ($product_results as &$product){
				$product['iaddress'] = $this->productModel->getIAddresses($product['id']);		
			}
			return ["success"=>true,"products"=>$product_results];
		}else{	
			return ["success"=>false,"errors"=>$errors];
		}
	}	
	
	
}
