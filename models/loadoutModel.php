<?php 
class loadoutModel extends App {  

	public $installed_time_utc='';
	public $start_block='';

	function setInstalledTime(){

		$stmt=$this->pdo->prepare("SELECT name,value FROM settings WHERE name = 'install_time_utc' OR name = 'start_block'");
		$stmt->execute([]);		
		if($stmt->rowCount()==0){
			return false;
		}
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);	
		foreach($rows as $row){
			if($row['name']=='install_time_utc'){
				$this->installed_time_utc = $row['value'];
			}else{
				$this->start_block = $row['value'];
			}	
		}
	}


	function getTransactionList(){
		$stmt=$this->pdo->prepare("
		SELECT i.*, res.*, res.out_message AS res_out_message
		FROM incoming as i 
		RIGHT JOIN orders as o
		ON FIND_IN_SET(i.id, o.incoming_ids)
		INNER JOIN responses as res 
		ON (o.id = res.order_id) 
		WHERE res.type = 'sale' OR res.type = 'token_sale' OR res.type = 'sc_sale'
		");
		$stmt->execute(array());
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $rows;
	}
	
		
}
