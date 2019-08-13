<?php

require_once 'app/Mage.php';

Varien_Profiler::enable();
Mage::setIsDeveloperMode(true);
ini_set('display_errors', 1);

umask(0);
Mage::app();

$obj = Mage::getModel('total/observer');

$obj->getClearsaleOrderStatus();

echo "OK";



