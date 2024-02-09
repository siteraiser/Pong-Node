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
			$settings = $this->settingsModel->getSettings();
		}
		return $settings;
		
		
		
	}
}