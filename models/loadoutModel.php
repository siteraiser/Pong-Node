<?php 
class loadoutModel extends App {  

	public $installed_time_utc='';

	function setInstalledTime(){

		$stmt=$this->pdo->prepare("SELECT value FROM settings WHERE name = 'install_time_utc'");
		$stmt->execute([]);		
		if($stmt->rowCount()==0){
			return false;
		}
		$row = $stmt->fetch(PDO::FETCH_ASSOC);		
		$this->installed_time_utc = $row['value'];
	}


	function getTransactionList(){
		$stmt=$this->pdo->prepare("
		SELECT i.*, ia.* , res.*, p.*, res.out_message AS res_out_message
		FROM incoming as i 
		LEFT JOIN products as p 
		ON (i.for_product_id = p.id)
		LEFT JOIN i_addresses as ia 
		ON (i.amount = ia.ask_amount AND i.port = ia.port AND p.id = ia.product_id)
		INNER JOIN responses as res 
		ON (i.id = res.incoming_id) 
		WHERE res.type = 'sale' OR res.type = 'sc_sale'
		");
		$stmt->execute(array());
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $rows;
	}
	
		
}
