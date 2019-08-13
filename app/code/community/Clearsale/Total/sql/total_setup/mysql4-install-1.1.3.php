<?php
$installer = $this;
$installer->startSetup();

$status = Mage::getModel('sales/order_status');
$status->setStatus('pending_clearsale');
$status->setLabel('Pending ClearSale');
$status->save();

$installer->endSetup();
	 