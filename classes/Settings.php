<?php 
class Settings extends App {  	
	function run(){
		$this->loadModel('settingsModel');
		
		if(!empty($_POST)){
			$this->settingsModel->setSettings();
			$settings = $this->settingsModel->getSettings();
			
			if(isset($_POST['register'])){
				if($_POST['register']==1){
					$this->loadModel('webApiModel');
					$registration = $this->webApiModel->register($settings);
					if($registration){
						$settings = $this->settingsModel->getSettings($this->settingsModel->setRegistration($registration));
					}
				}
			}
		}else{
			//Not posting anything			
			$settings = $this->settingsModel->getSettings();
			foreach($settings as &$setting){
				//Get wallet address if it hasn't been changed yet.
				if($setting['name']=='web_api_wallet' && $setting['value'] == 'Wallet Address'){
					$this->loadModel('deroApiModel');
					$result = $this->deroApiModel->getAddress();
					$result = json_decode($result);
					//Getting wallet...
					if(!isset($result->errors) && isset($result->result)){	
						$setting['value'] = $result->result->address;
					}		
				}
			}
			unset($setting);
		}
		return $settings;
		
		
		
	}
}
