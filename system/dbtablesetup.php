<?php


$table_setup = <<<EOD
CREATE TABLE settings (
	id smallint(6) unsigned NOT NULL auto_increment,
	name varchar(100) NULL,
	value varchar(200) NULL,
	PRIMARY KEY (id)
) ENGINE=InnoDB;


CREATE TABLE products (
	id smallint(6) unsigned NOT NULL auto_increment,
	p_type varchar(20) NULL,
	label varchar(1500) NULL,
	details varchar(2500) NULL,
	out_message varchar(144) NULL,
	out_message_uuid tinyint(1) DEFAULT 0,
	api_url varchar(256) NULL,
	scid varchar(64) NULL,
	respond_amount int(25),
	inventory int(25),
	image longblob,
	image_hash varchar(16),
	lastupdate timestamp DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id)
) ENGINE=InnoDB;


CREATE TABLE i_addresses (
	id smallint(6) unsigned NOT NULL auto_increment,	
	iaddr varchar(1500) NULL,
	ask_amount int(25), 
	comment varchar(400) NULL,
	port smallint(6) NULL,
	product_id int(25),
	ia_scid varchar(64) NULL,
	ia_respond_amount int(25),
	ia_inventory int(25),
	status tinyint(1) NULL,	
	lastupdate timestamp DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id)
) ENGINE=InnoDB;

CREATE TABLE incoming (
	id smallint(6) unsigned NOT NULL auto_increment,
	txid varchar(150) NULL,	
	buyer_address varchar(100) NULL,
	amount int(25) unsigned,
	port smallint(6) NULL,
	for_product_id smallint(6) NULL,
	for_ia_id smallint(6) NULL,
	product_label varchar(150) NULL,
	successful tinyint(1) DEFAULT 0,	
	processed tinyint(1) DEFAULT 0,
	block_height int(15),
	time_utc timestamp NULL,
	lastupdate timestamp DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id)
) ENGINE=InnoDB;

CREATE TABLE responses (
	id smallint(6) unsigned NOT NULL auto_increment,
	incoming_id smallint(6) unsigned NOT NULL,
	txid varchar(150) NULL,
	txids varchar(2000) NULL,
	type varchar(150) NULL,
	buyer_address varchar(100) NULL,
	out_amount int(25) unsigned,
	port smallint(6) NULL,	
	out_message varchar(144) NULL,
	out_message_uuid tinyint(1) DEFAULT 0,
	uuid varchar(128) NULL,	
	api_url varchar(256) NULL,
	out_scid varchar(64) NULL,
	crc32 varchar(16) NULL,
	ship_address varchar(144) NULL,
	confirmed tinyint(1) DEFAULT 0,
	time_utc timestamp NULL,
	t_block_height int(15),
	lastupdate timestamp DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id)
) ENGINE=InnoDB;

CREATE TABLE pending (
	id smallint(6) unsigned NOT NULL auto_increment,
	url varchar(256) NULL,
	json_text blob NULL,
	method varchar(150) NULL,
	aid varchar(100) NULL,
	error varchar(200) NULL,
	lastupdate timestamp DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id)
) ENGINE=InnoDB;
EOD;




$result = $this->pdo->query("SHOW TABLES LIKE 'products'");
if($result !== false && $result->rowCount() > 0){	
}else{
	//create tables
	$result = $this->pdo->query($table_setup);
	//save time installed
	$given = new DateTime();
	$given->setTimezone(new DateTimeZone("UTC"));
	$startup_time = $given->format("Y-m-d H:i:s");
	//set a checkin time in the future
	$given->modify('+5 minutes');
	$next_checkin_utc = $given->format("Y-m-d H:i:s");
	$this->pdo->query("
	INSERT INTO settings (name,value) VALUES('install_time_utc','$startup_time');
	
	INSERT INTO settings (name,value) VALUES('dero_api_ip','127.0.0.1');
	INSERT INTO settings (name,value) VALUES('dero_api_port','10103');	
	INSERT INTO settings (name,value) VALUES('dero_api_user','secret');
	INSERT INTO settings (name,value) VALUES('dero_api_pass','pass');

	INSERT INTO settings (name,value) VALUES('web_api_url','https://ponghub.com/papi');
	INSERT INTO settings (name,value) VALUES('web_api_user','Dero User Name');
	INSERT INTO settings (name,value) VALUES('web_api_wallet','Wallet Address');
	INSERT INTO settings (name,value) VALUES('web_api_id','');
	
	INSERT INTO settings (name,value) VALUES('next_checkin_utc','$next_checkin_utc');
	
	");
	
}
unset($table_setup);
unset($result);

?>
