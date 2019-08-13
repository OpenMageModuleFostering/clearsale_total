<?php

class Clearsale_Total_Model_Authorizenet extends Mage_Paygate_Model_Authorizenet 
{
    protected function _registercard(varien_object $response, mage_sales_model_order_payment $payment)
    {
    
        try
	{
	
	$csLog = Mage::getSingleton('total/log');			
	$csLog->log("Teste");		
	
	 $cardInfo = parent::_registercard($response,$payment);	
	 
	 $avs =$response->getAvsResultCode();

	 $responseCode = $response->getCardCodeResponseCode();

	 $payment->setAdditionalInformation('clearsaleCCAvs', $avs);
	 $payment->setAdditionalInformation('clearsaleCCResponseCode', $responseCode);
	 return $cardInfo;
	 
	}catch (Exception $e) {
		$csLog = Mage::getSingleton('total/log');			
		$csLog->log($e->getMessage());			
	}
	 
    }
}
