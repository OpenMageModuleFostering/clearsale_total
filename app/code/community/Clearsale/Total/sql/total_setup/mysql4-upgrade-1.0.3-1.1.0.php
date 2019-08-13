<?php

$installer = $this;
 
$installer->startSetup();

$sql=<<<SQLTEXT
CREATE TABLE IF NOT EXISTS `clearsale_order_control` (
	order_id VARCHAR(20) ,
	diagnostics TEXT,
	attempts INT,
	dt_sent DATETIME  DEFAULT NULL,
	dt_update DATETIME  DEFAULT NULL
);
SQLTEXT;

$installer->run($sql);


$installer->endSetup();
	 