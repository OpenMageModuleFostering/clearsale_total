<?php

class  Clearsale_Total_Model_Order_Business_Object
{
	public $StatusHandle;
	public $Http;
	
	function __construct() {
		$this->StatusHandle = new Clearsale_Total_Model_Utils_Status();
		$this->Http = new Clearsale_Total_Model_Utils_HttpHelper();
	}
	
	public function send($requestSend,$enviroment) {
		$url = $enviroment."api/order/send/";
		$response = $this->Http->postData($requestSend, $url);	
		return $response;
	}
	
	public function get($requestGet,$enviroment)
	{
		$url = $enviroment."api/order/get/";
		$response = $this->Http->postData($requestGet, $url);	
		return $response;
	}


	public function save($order)
	{			
	 $magentoStatus = $this->StatusHandle->toMagentoStatus($order->Status);
	 
	 if($magentoStatus)
	  {
	   if($magentoStatus != "")
		{
	     $this->setOrderStatus($order->ID,$magentoStatus);
		}
	  }
	}
	
	public function update($Order)
	{ 
	  $magentoStatus = $this->StatusHandle->toMagentoStatus($Order->Status);
	  
	  if($magentoStatus)
	  {
	    if($magentoStatus != "")
		{
	      $this->setOrderStatus($Order->ID,$magentoStatus);
	    }
	  }
	  
	}
	
	public function setOrderStatus($orderid,$status)
	{	
		$order = Mage::getModel('sales/order')->loadByIncrementId($orderid);
		if($order->getStatus() != $status)
		{
		 $order->setStatus($status);	
		 $order->addStatusToHistory($status, 'Clearsale Status Update', false);
		 $order->save();
		}		
	}
	
		
	private function insertClearsaleOrderControl($orderId,$message)
	{	
		try{
			$orderArray["order_id"] = $orderId;
			$orderArray["diagnostics"] = $message;
			$orderArray["attempts"] = 1;
			$orderArray["dt_update"] = date('Y-m-d H:i:s');
                        $orderArray["dt_sent"] = '';
			
			$connection = Mage::getSingleton('core/resource')->getconnection('core_write');	
			$connection->insert('clearsale_order_control', $orderArray);					 						
		} 
		catch (Exception $e)
		{	
			$CSLog = Mage::getSingleton('total/log');			
			$CSLog->log($e->getMessage());			
		}
	}	
	
	
	private function updateClearsaleOrderControl($orderId,$message,$attempts,$sent)
	{
		try {  
			$orderArray["order_id"] = $orderId;
			$orderArray["diagnostics"] = $message;
			$orderArray["attempts"] = $attempts;			
		 	$orderArray["dt_update"] = date('Y-m-d H:i:s');
                        
                        echo "Tentativas $attempts";
			
			if($sent)
			{
			 $orderArray["dt_sent"] = date('Y-m-d H:i:s');	
			}
			
			$connection = Mage::getSingleton('core/resource')->getconnection('core_write'); 
			$__where = $connection->quoteInto('order_id = ?', $orderArray["order_id"]);
			$connection->update('clearsale_order_control', $orderArray, $__where);	
                        echo $__where;
			
		} catch (Exception $e){  
                    echo "Error: ".$e->getMessage();
			$CSLog = Mage::getSingleton('total/log');			
			$CSLog->log($e->getMessage());	 
		}  
		
	}
	
	
	private function selectClearsaleOrderControl($maxAttemps)
	{
		try {  
			$connection = Mage::getSingleton('core/resource')->getconnection('core_read'); 
			$query = "SELECT * FROM `clearsale_order_control` WHERE `dt_sent` = '0000-00-00 00:00:00' AND `attempts` <=".$maxAttemps;
			$results = $connection->fetchAll($query);

			return $results;
                        			
		} catch (Exception $e){  
			$CSLog = Mage::getSingleton('total/log');			
			$CSLog->log($e->getMessage());	 
		}  
		
	}
	
			

	
	public function createOrderControl($orderid,$message)
	{	
	  $this->insertClearsaleOrderControl($orderid,$message);		
	}
	
	public function setOrderControl($orderId,$sent,$attemps,$message)
	{	
	   $this->updateClearsaleOrderControl($orderId,$message,$attemps,$sent);
	}
	
	public function getOrderControl()
	{
            $maxAttemps = 5;
	   return $this->selectClearsaleOrderControl($maxAttemps);
	}
	
	
	public function objectOrderToArray($order)
	{
		$array_order["order_id"] = $order->ID;
		$array_order["clearsale_status"] = $order->Status;
		$array_order["score"] = $order->Score;	
		return $array_order;
	}

}



