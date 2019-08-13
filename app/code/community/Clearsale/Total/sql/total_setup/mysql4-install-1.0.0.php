<?php
$installer = $this;
$installer->startSetup();
$sql=<<<SQLTEXT
CREATE TABLE clearsale_order_description (
	order_id VARCHAR(20) ,
	clearsale_status VARCHAR(50),
	score VARCHAR(5),
	diagnostics VARCHAR(255),
	dt_sent TIMESTAMP,
	dt_update TIMESTAMP
);
SQLTEXT;

$installer->run($sql);

$status = Mage::getModel('sales/order_status');
$status->setStatus('approved_clearsale');
$status->setLabel('Approved ClearSale');
$status->save();

$status = Mage::getModel('sales/order_status');
$status->setStatus('reproved_clearsale');
$status->setLabel('Reproved ClearSale');
$status->save();

$status = Mage::getModel('sales/order_status');
$status->setStatus('canceled_clearsale');
$status->setLabel('Canceled ClearSale');
$status->save();

$status = Mage::getModel('sales/order_status');
$status->setStatus('analysing_clearsale');
$status->setLabel('Analysing ClearSale');
$status->save();


$installer->endSetup();
	 