<?php

require_once('app/Mage.php');
		Mage::app();
		Varien_Profiler::enable();
Mage::setIsDeveloperMode(true);
ini_set('display_errors', 1);
umask(0);

	try{
		$isActive = Mage::getStoreConfig("clearsale_total/general/active");
		$csLog = Mage::getSingleton('total/log');	
		
		$maxTries  = 5;
		
		if ($isActive) 
		{			
			$obj = Mage::getModel('total/observer');
                        $obj->retry();
						echo "ok";
		}

	}catch (Exception $e) 
	{
		print_r($e->getMessage());
		$csLog = Mage::getSingleton('total/log');			
		$csLog->log($e->getMessage());		
	}
