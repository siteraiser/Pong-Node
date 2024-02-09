<?php 
require_once('system/startup.php');
$Settings = new Settings;
header('Content-type: application/json');
echo json_encode($Settings->run());	
