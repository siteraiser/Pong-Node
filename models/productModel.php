<?php 
class productModel extends App {  
	//also in initialize...
	function getProductsList(){
		$stmt=$this->pdo->prepare("SELECT * FROM products");
		$stmt->execute(array());
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $rows;
	}
	
	function getProductById($pid){
		$stmt=$this->pdo->prepare("SELECT * FROM products WHERE id=?");
		$stmt->execute(array($pid));
		$rows = $stmt->fetch(PDO::FETCH_ASSOC);
		return $rows;
	}

	function getIAddressById($iaddr_id){
		$stmt=$this->pdo->prepare("SELECT * FROM i_addresses WHERE id = ?");
		$stmt->execute(array($iaddr_id));
		$rows = $stmt->fetch(PDO::FETCH_ASSOC);
		return $rows;
	}
	
	function getIAddresses($product_id){
		$stmt=$this->pdo->prepare("SELECT * FROM i_addresses WHERE product_id = ?");
		$stmt->execute(array($product_id));
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $rows;
	}
}
