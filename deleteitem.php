<?php 
require_once('system/startup.php');
$Product = new Product;
header('Content-type: application/json');

	//$json = json_decode($_POST['method'],true);	
	$post = file_get_contents("php://input");
	$json = json_decode($post,true);		
	
if($json['type'] == 'iaddress' && is_numeric($json['id'])){
	
	$result = $Product->deleteIntegratedAddress($json['id']);
	
}else if($json['type'] == 'product' && is_numeric($json['id'])){
	$result = $Product->deleteProduct($json['id']);
}
echo json_encode($result);	


