<?php 
class addProductModel extends App {  
	
	function insertIntegratedAddress($product_id,$iaddr){

		$query='INSERT INTO i_addresses (
			iaddr,
			ask_amount,
			comment,
			port,
			product_id,
			ia_scid,
			ia_respond_amount,
			ia_inventory,
			status
			)
			VALUES
			(?,?,?,?,?,?,?,?,?)';	
		
		$array=array(
			$iaddr,
			$_POST['ask_amount'],
			$_POST['comment'],
			$_POST['port'],
			$product_id,
			'',
			0,
			$_POST['inventory'] == '' ? 0 : $_POST['inventory'],
			1
			);				
				
		$stmt=$this->pdo->prepare($query);
		$stmt->execute($array);		
		if($stmt->rowCount()==0){
			return false;
		}
		return $this->pdo->lastInsertId('id');
	}



	function integratedAddressExists($iaddr){

		$stmt=$this->pdo->prepare("SELECT * FROM i_addresses INNER JOIN products ON i_addresses.product_id = products.id 
		WHERE iaddr = ? ");//AND i_addresses.status = '1'
		$stmt->execute([$iaddr]);		
		if($stmt->rowCount()==0){
			return false;
		}
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		
		return $row['comment'];
	}


	function portExists($port,$ask_amount){

		$stmt=$this->pdo->prepare(
		"SELECT * FROM i_addresses 	
		INNER JOIN products ON i_addresses.product_id = products.id 
		WHERE i_addresses.port = ? AND i_addresses.ask_amount = ?");
		$stmt->execute([$port,$ask_amount]);		
		if($stmt->rowCount()==0){
			return false;
		}
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		
		return ["comment"=>$row['comment'],"iaddr"=>$row['iaddr']];
	}

	function insertProduct(){

		$query='INSERT INTO products (
			label,
			details,
			out_message,
			out_message_uuid,
			scid,
			respond_amount,
			inventory
			)
			VALUES
			(?,?,?,?,?,?,?)';	
		
		$array=array(
			$_POST['label'],
			$_POST['details'],
			$_POST['out_message'],
			isset($_POST['out_message_uuid']) ? 1 : 0,
			$_POST['scid'],
			($_POST['respond_amount']=='' || $_POST['respond_amount'] < 1 ? 1 :$_POST['respond_amount']),
			0
			);				
				
		$stmt=$this->pdo->prepare($query);
		$stmt->execute($array);		
		if($stmt->rowCount()==0){
			return false;
		}
		return $this->pdo->lastInsertId('id');
	}
}
