<?php 
class settingsModel extends App {  

	function getSettings(){

		$stmt=$this->pdo->prepare("SELECT * FROM settings WHERE NOT(name = 'install_time_utc')");
		$stmt->execute([]);		
		if($stmt->rowCount()==0){
			return false;
		}
		return $stmt->fetchAll(PDO::FETCH_ASSOC);		
		
	}
	function setSettings(){

		$query="
		UPDATE settings SET value=:dero_api_ip WHERE name = 'dero_api_ip';			
		UPDATE settings SET value=:dero_api_port WHERE name = 'dero_api_port';		
		UPDATE settings SET value=:dero_api_user WHERE name = 'dero_api_user';		
		UPDATE settings SET value=:dero_api_pass WHERE name = 'dero_api_pass';		
		UPDATE settings SET value=:web_api_url WHERE name = 'web_api_url';		
		UPDATE settings SET value=:web_api_user WHERE name = 'web_api_user';		
		UPDATE settings SET value=:web_api_wallet WHERE name = 'web_api_wallet';	
		";	
		
		$stmt=$this->pdo->prepare($query);
		$stmt->execute(array(
			':dero_api_ip'=>$_POST['dero_api_ip'],
			':dero_api_port'=>$_POST['dero_api_port'],
			':dero_api_user'=>$_POST['dero_api_user'],
			':dero_api_pass'=>$_POST['dero_api_pass'],
			
			':web_api_url'=>$_POST['web_api_url'],
			':web_api_user'=>$_POST['web_api_user'],
			':web_api_wallet'=>$_POST['web_api_wallet']
			));				
					
		if($stmt->rowCount()==0){
			return false;
		}
		return true;	
		
	}
	
		function setRegistration($registration_id){

		$query="
		UPDATE settings SET value=:web_api_id WHERE name = 'web_api_id';	
		";	
		
		$stmt=$this->pdo->prepare($query);
		$stmt->execute(array(
			':web_api_id'=>$registration_id
			));				
					
		if($stmt->rowCount()==0){
			return false;
		}
		return true;	
		
	}
}	
