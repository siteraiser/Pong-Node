<?php 
class editProductModel extends App {  
	function toggleIAddr($id,$status){	
		
		$query='UPDATE i_addresses SET 
			status=:status
			WHERE id=:id';	
		
		$stmt=$this->pdo->prepare($query);
		$stmt->execute(array(
			':status'=>$status,
			':id'=>$id));				
					
		if($stmt->rowCount()==0){
			return false;
		}
		return true;
	}
	function setInventory($id){	
		
		$query='UPDATE i_addresses SET 
			ia_inventory=:ia_inventory
			WHERE id=:id';	
		
		$stmt=$this->pdo->prepare($query);
		$stmt->execute(array(
			':ia_inventory'=>$_POST['ia_inventory'][$id],
			':id'=>$id));				
					
		if($stmt->rowCount()==0){
			return false;
		}
		return true;
	}
	function setSCID($id){	
		
		$query='UPDATE i_addresses SET 
			ia_scid=:ia_scid
			WHERE id=:id';	
		
		$stmt=$this->pdo->prepare($query);
		$stmt->execute(array(
			':ia_scid'=>$_POST['ia_scid'][$id],
			':id'=>$id));				
					
		if($stmt->rowCount()==0){
			return false;
		}
		return true;
	}
	function setIARespondAmount($id){	
		
		$query='UPDATE i_addresses SET 
			ia_respond_amount=:ia_respond_amount
			WHERE id=:id';	
		
		$stmt=$this->pdo->prepare($query);
		$stmt->execute(array(
			':ia_respond_amount'=>($_POST['ia_respond_amount'][$id] == '' ? 0 : $_POST['ia_respond_amount'][$id]),
			':id'=>$id));				
					
		if($stmt->rowCount()==0){
			return false;
		}
		return true;
	}

//more specific to editing...

	function sameIntegratedAddress(){
		
		$stmt=$this->pdo->prepare(
		"SELECT * FROM i_addresses 	
		WHERE i_addresses.product_id = ? AND i_addresses.comment = ? AND i_addresses.ask_amount = ? AND i_addresses.port = ?" );// AND i_addresses.status = '1'
		$stmt->execute([$_POST['pid'],$_POST['comment'],$_POST['ask_amount'],$_POST['port']]);		
		if($stmt->rowCount()==0){
			return false;
		}	
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row['iaddr'];
	}


	//also in addproduct
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
			0,
			1
			);				
				//$_POST['ia_respond_amount'] == '' ? 0 : $_POST['ia_respond_amount'],
		$stmt=$this->pdo->prepare($query);
		$stmt->execute($array);		
		if($stmt->rowCount()==0){
			return false;
		}
		return $this->pdo->lastInsertId('id');
	}


	function integratedAddressExists($iaddr){

		$stmt=$this->pdo->prepare("SELECT * FROM i_addresses INNER JOIN products ON i_addresses.product_id = products.id 
		WHERE iaddr = ? AND i_addresses.status = '1'");
		$stmt->execute([$iaddr]);		
		if($stmt->rowCount()==0){
			return false;
		}
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		
		return ["comment"=>$row['comment'],"ask_amount"=>$row['ask_amount'],"port"=>$row['port']];
	}

	function integratedAddressExistsElsewhere($iaddr){

		$stmt=$this->pdo->prepare("SELECT * FROM i_addresses WHERE iaddr = ? AND status = '1' AND NOT(id = ?) AND NOT(product_id = ?)");
		$stmt->execute([$iaddr['iaddr'],$iaddr['id'],$iaddr['product_id']]);		
		
		if($stmt->rowCount()==0){
			return false;
		}
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}


	function portExists($port,$ask_amount){

		$stmt=$this->pdo->prepare(
		"SELECT * FROM i_addresses 	
		INNER JOIN products ON i_addresses.product_id = products.id 
		WHERE i_addresses.port = ? AND i_addresses.status = '1' AND i_addresses.ask_amount = ?");
		$stmt->execute([$port,$ask_amount]);		
		if($stmt->rowCount()==0){
			return false;
		}
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		
		return ["comment"=>$row['comment'],"iaddr"=>$row['iaddr']];
	}
	
	function getProductImageHash($id){

		$stmt=$this->pdo->prepare(
		"SELECT id,image_hash FROM products 	
		WHERE id = ?");
		$stmt->execute([$id]);		
		if($stmt->rowCount()==0){
			return false;
		}
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		
		return $row['image_hash'];
	}
	
	function updateProduct(){
		$new_image = false;
		$image_hash = '';
		if($_POST['image']!=''){
			$image_hash = crc32($_POST['image']);
		}
		$current_hash = $this->getProductImageHash($_POST['pid']);
		if($current_hash != $image_hash){
			$new_image = true;
		}
		
		
		$query='UPDATE products SET 
			p_type=:p_type,
			label=:label,
			details=:details,
			out_message=:out_message,
			out_message_uuid=:out_message_uuid,
			api_url=:api_url,
			scid=:scid,
			respond_amount=:respond_amount,
			inventory=:inventory,
			image=:image,
			image_hash=:image_hash
			WHERE id=:id';
		
		
		$stmt=$this->pdo->prepare($query);
		$stmt->execute(array(
			':p_type'=>$_POST['p_type'],
			':label'=>$_POST['label'],
			':details'=>$_POST['details'],				
			':out_message'=>$_POST['out_message'],
			':out_message_uuid'=>isset($_POST['out_message_uuid']) ? 1 : 0,		
			':api_url'=>$_POST['api_url'],
			':scid'=>$_POST['scid'],
			':respond_amount'=>($_POST['respond_amount']=='' || $_POST['respond_amount'] < 1 ? 1 :$_POST['respond_amount']),
			':inventory'=>$_POST['inventory'] == '' ? 0 : $_POST['inventory'],
			':image'=>$_POST['image'],
			':image_hash'=>$image_hash,
			':id'=>$_POST['pid']));				
					
		
		return ['id'=>$_POST['pid'],'new_image'=>$new_image];
	}
}
