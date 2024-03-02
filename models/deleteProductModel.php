<?php 
class deleteProductModel extends App {  
	
	function deleteIntegratedAddress($iaddr_id){
		$stmt=$this->pdo->prepare("	
			SELECT * FROM i_addresses 
			JOIN incoming ON ((i_addresses.id = incoming.for_ia_id) OR ISNULL(incoming.for_ia_id)) 
			JOIN orders ON FIND_IN_SET(incoming.id, orders.incoming_ids)
			JOIN responses ON (orders.id = responses.order_id)
			WHERE i_addresses.id = ? AND (orders.order_status != 'confirmed' OR responses.confirmed = '0' OR incoming.processed = '0')
		");
		$stmt->execute([$iaddr_id]);		
		if($stmt->rowCount()!=0){
			return ['Still confirming, try again soon.'];
		}
		

		$stmt=$this->pdo->prepare("SELECT product_id FROM i_addresses WHERE id = ?");
		$stmt->execute([$iaddr_id]);		
		if($stmt->rowCount()==0){
			return ['Couldn\'t find product'];
		}
		
		//$product_id = $stmt->fetch(PDO::FETCH_ASSOC);

		
		$stmt=$this->pdo->prepare("DELETE FROM i_addresses WHERE id = ?");
		$stmt->execute([$iaddr_id]);		
		if($stmt->rowCount()==0){
			return ['Already Non Existent'];
		}
		return true;
	}

	function deleteProduct($product_id){

		$stmt=$this->pdo->prepare("	
			SELECT * FROM products 
			JOIN i_addresses ON i_addresses.product_id = products.id  
			JOIN incoming ON ((i_addresses.id = incoming.for_ia_id) OR ISNULL(incoming.for_ia_id)) 
			JOIN orders ON FIND_IN_SET(incoming.id, orders.incoming_ids)
			JOIN responses ON (orders.id = responses.order_id)
			WHERE products.id = ? AND (orders.order_status != 'confirmed' OR responses.confirmed = '0' OR incoming.processed = '0')
		");
		$stmt->execute([$product_id]);		
		if($stmt->rowCount()!=0){
			return ['Still confirming, try again soon.'];
		}
		
		
		$stmt=$this->pdo->prepare("	
			DELETE FROM i_addresses 
			WHERE product_id = ? ;
			
			DELETE FROM products WHERE id = ?
			");
		$stmt->execute([$product_id,$product_id]);		
		if($stmt->rowCount()==0){
			return [];
		}
		return [];
		
/* even safer, but too safe... lol
	SELECT * FROM incoming 
		JOIN orders ON FIND_IN_SET(incoming.id, orders.incoming_ids)
		WHERE incoming.for_product_id = ? AND orders.order_status != 'confirmed'
		//return 	$stmt->fetch(PDO::FETCH_ASSOC);
*/
	}


	
}