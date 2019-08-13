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
	 $this->insertClearsaleOrderDiagnostic($order);
	 $magentoStatus = $this->StatusHandle->toMagentoStatus($order->Status);
	 $this->setOrderStatus($order->ID,$magentoStatus);
	}
	
	public function update($Order)
	{ 
	  $this->updateClearsaleOrderDiagnostic($Order);
	  $magentoStatus = $this->StatusHandle->toMagentoStatus($Order->Status);
	  $this->setOrderStatus($Order->ID,$magentoStatus);
	  
	}
		
	public function insertClearsaleOrderDiagnostic($Order)
	{	
		try{
			$orderArray = $this->objectOrderToArray($Order);
			$orderArray["dt_sent"] = date('Y-m-d H:i:s');
			$connection = Mage::getSingleton('core/resource')->getconnection('core_write');	
			$connection->insert('clearsale_order_diagnostic', $orderArray);					 						
		} 
		catch (Exception $e)
		{	
			$CSLog = Mage::getSingleton('total/log');			
			$CSLog->log($e->getMessage());			
		}
	}	
	
	
	public function updateClearsaleOrderDiagnostic($Order)
	{
		try {  
			$orderArray = $this->objectOrderToArray($Order);			
		 	$orderArray["dt_update"] = date('Y-m-d H:i:s');
			
			$connection = Mage::getSingleton('core/resource')->getconnection('core_write'); 
			$__where = $connection->quoteInto('order_id = ?', $orderArray["order_id"]);
			$connection->update('clearsale_order_diagnostic', $orderArray, $__where);	
			
		} catch (Exception $e){  
			$CSLog = Mage::getSingleton('total/log');			
			$CSLog->log($e->getMessage());	 
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
	
	public function objectOrderToArray($order)
	{
		$array_order["order_id"] = $order->ID;
		$array_order["clearsale_status"] = $order->Status;
		$array_order["score"] = $order->Score;	
		return $array_order;
	}

}



