<?php

class Clearsale_Total_Model_Order_Entity_RequestOrder
{
	public  $ApiKey ;
	public  $LoginToken;
	public  $Orders;
	public $AnalysisLocation;
	function __construct() {
		$this->Orders = array();
	}
}

class Clearsale_Total_Model_Observer
{
	
	public function create($evt)
	{
		$csLog = Mage::getSingleton('total/log');
	try {	

		$isActive = Mage::getStoreConfig("clearsale_total/general/active");

		if ($isActive) 
	    {
			$csLog->log('gravar pedido ' . json_encode($evt));	
			$session = Mage::getSingleton("core/session")->getEncryptedSessionId();
			$orderBO = Mage::getModel('total/order_business_object');	
			$magentoOrder = $evt->getOrder();
			
			if(!empty($magentoOrder))
			{	
				$orderID = $magentoOrder->getRealOrderId();
				
				if(!$orderID)
				{
					return;
				}
				
				$payment = $magentoOrder->getPayment();
				if($payment)
				{
					if(!$this->orderExists($orderID))
					{		
						$csLog->log('Sending to ClearSale OrderID - '. $orderID);
						$CreditcardMethods = explode(",", Mage::getStoreConfig("clearsale_total/general/credicardmethod"));
						$magentoStatus = $magentoOrder->getStatus();
						
						$csLog->log('Status : ' . $magentoStatus . ' ID: ' . $orderID  );
						
						
							if (in_array($payment->getMethodInstance()->getCode(), $CreditcardMethods))
							{
								$order = new Clearsale_Total_Model_Order_Entity_Status();
								$payment->setAdditionalInformation('clearsaleSessionID', $session);				
								$order->ID = $orderID;
								$order->Status = 'PED';
								$orderBO->save($order);			 
								$csLog->log('Sent to ClearSale OrderID - '. $orderID);
								$this->setAsSent($orderID);
							}
									
					}else
					{
						$csLog->log('Order Update : ID: ' . $orderID  );
					}
				}
			}
		 }
		}catch(Exception $e){                    
			$csLog->log($e->getMessage());		
		}
	}
				
	public function getClearsaleOrderStatus()
	{
	 $isActive = Mage::getStoreConfig("clearsale_total/general/active");

		
		if ($isActive) 
		{
			
			$orders = Mage::getModel('sales/order')->getCollection()
			->addFieldToFilter('status', 'analysing_clearsale');	
			
			if($orders)
			{	
				foreach ($orders as $order) {
				
				$storeId = $order->getStoreId();
				
				$environment = Mage::getStoreConfig("clearsale_total/general/environment",$storeId);
				$analysisLocation = Mage::getStoreConfig("clearsale_total/general/analysislocation",$storeId);
				$authBO = Mage::getModel('total/auth_business_object');
				$authResponse = $authBO->login($environment);
				$orderBO = Mage::getModel('total/order_business_object');				
				
					if($authResponse)
					{
						$orderId = $order->getRealOrderId();		
						$requestOrder =  Mage::getModel('total/order_entity_requestorder');
						$requestOrder->ApiKey =  Mage::getStoreConfig("clearsale_total/general/key",$storeId);
						$requestOrder->LoginToken = $authResponse->Token->Value;
						$requestOrder->AnalysisLocation = $analysisLocation;
						$requestOrder->Orders = array();
						$requestOrder->Orders[0] = $orderId;			
						$response = $orderBO->get($requestOrder,$environment);	

						if($response->HttpCode == 200)
						{
						   $responseOrder = json_decode($response->Body);						
							$orderBO->Update($responseOrder->Orders[0]);
							
							$order = $order->loadByIncrementId($orderId);
							if($order->getStatus() == 'approved_clearsale')
							{
								$createInvoice = Mage::getStoreConfig("clearsale_total/general/create_invoice");
								
								if($createInvoice)
								{
									echo "Criar Invoice <br />";
									$this->createInvoice($order);
								}
							}
						}						
					}
				}		
			}
		}
	}

	public function toClearsaleOrderObject(Mage_Sales_Model_Order  $order,$isReanalysis,$location){
		
		try {
			
			$customerModel = Mage::getModel('customer/customer');
			$customer = $customerModel->load($order->getCustomerId());			
			$email =$customer->getEmail();			
			
			if (!$email) 
			{
				$email = $order->getBillingAddress()->getEmail();
			}	

			if($location == "BRA")
			{
				$legalDocument = preg_replace('/[^0-9]/', '', $customer->getTaxvat());
				$currency = "BRL";
			}else
			{
				$currency = "USD";
				$legalDocument = "";
			}
			
			$date = new DateTime($order->getCreatedAt());
			$date = date('c', strtotime($order->getCreatedAt()));			

			$clearsaleOrder = new Clearsale_Total_Model_Order_Entity_Order();
			$clearsaleOrder->ID = $order->getRealOrderId();
			$clearsaleOrder->IP = $order->getRemoteIp();
			$clearsaleOrder->Currency = $currency;
			$clearsaleOrder->Date = $date;
			$clearsaleOrder->Reanalysis = $isReanalysis;
			$clearsaleOrder->Email = $email;
			$clearsaleOrder->TotalOrder = number_format(floatval($order->getGrandTotal()), 2, ".", "");
			
			$items = $order->getAllItems();
			$payment = $order->getPayment();
			
			$billingAddress = $order->getBillingAddress();
			$shippingAddress = $order->getShippingAddress();
			
			$dob = $customer->getDob();
			
			if(!$dob)
			{
			 $dob = $date;
			}
			
			if(!$billingAddress)
			{
				$billingAddress = $shippingAddress;
			}
			
			if(!$shippingAddress)
			{
				$shippingAddress = $billingAddress;
			}			
			
			$billingName = $billingAddress->getFirstname() . " " . $billingAddress->getMiddlename() . " " . $billingAddress->getLastname();
			$billingName = trim(str_replace("  ", " ", $billingName));
			$billingCountry = Mage::getModel('directory/country')->loadByCode($billingAddress->getCountry());
			$billingPhone = preg_replace('/[^0-9]/', '', $billingAddress->getTelephone());

			$shippingName = $shippingAddress->getFirstname() . " " . $shippingAddress->getMiddlename() . " " . $shippingAddress->getLastname();
			$shippingName = trim(str_replace("  ", " ", $shippingName));
			$shippingCountry = Mage::getModel('directory/country')->loadByCode($shippingAddress->getCountry());
			$shippingPhone = preg_replace('/[^0-9]/', '', $shippingAddress->getTelephone());

			$paymentType = 1;
			$creditcardBrand = 0;
			$paymentIndex = 0;

			$creditcardMethods = explode(",", Mage::getStoreConfig("clearsale_total/general/credicardmethod"));			
			
			$clearsaleOrder->Payments[$paymentIndex] = new Clearsale_Total_Model_Order_Entity_Payment();
			$clearsaleOrder->Payments[$paymentIndex]->Amount = number_format(floatval($order->getGrandTotal()), 2, ".", "");
			$clearsaleOrder->Payments[$paymentIndex]->Type = 14;	
			$clearsaleOrder->Payments[$paymentIndex]->CardType = 4;
			$clearsaleOrder->Payments[$paymentIndex]->Date = $date;	

			$payment->setAdditionalInformation('clearsale', 'ok');
			
			if($payment->getAdditionalInformation('clearsaleCCNumber'))
			{
				$clearsaleOrder->Payments[$paymentIndex]->CardEndNumber = $payment->getAdditionalInformation('clearsaleCCLast4');
				$clearsaleOrder->Payments[$paymentIndex]->CardBin =  $payment->getAdditionalInformation('clearsaleCCBin');
				$clearsaleOrder->Payments[$paymentIndex]->CardHolderName = $payment->getAdditionalInformation('clearsaleCcOwner');				
				$clearsaleOrder->Payments[$paymentIndex]->CardType = $this->cardType($payment->getAdditionalInformation('clearsaleCCNumber'));
				$clearsaleOrder->Payments[$paymentIndex]->PaymentTypeID = 1;	
				$clearsaleOrder->Payments[$paymentIndex]->Type = 1;	
				 
				if($payment->getAdditionalInformation('clearsaleCCAvs'))
				{ 
				   //AVS
				   $clearsaleOrder->Customfields[0]->Type = 1;
				   $clearsaleOrder->Customfields[0]->Name = 'AVS_RESPONSE';
				   $clearsaleOrder->Customfields[0]->Value = $payment->getAdditionalInformation('clearsaleCCAvs');
				   
				   //Credicard Response Code
				   $clearsaleOrder->Customfields[1]->Type = 1;
				   $clearsaleOrder->Customfields[1]->Name = 'CC_RESPONSE';
				   $clearsaleOrder->Customfields[1]->Value = $payment->getAdditionalInformation('clearsaleCCResponseCode');				   
				}				
			}
			
			if($payment->getAdditionalInformation('avsPostalCodeResponseCode'))
			{ 
			   $clearsaleOrder->Customfields[0]->Type = 1;
			   $clearsaleOrder->Customfields[0]->Name = 'avsPostalCodeResponseCode';
			   $clearsaleOrder->Customfields[0]->Value = $payment->getAdditionalInformation('avsPostalCodeResponseCode');
			} 
			
			if($payment->getAdditionalInformation('avsStreetAddressResponseCode'))
			{ 				
			   $clearsaleOrder->Customfields[1]->Type = 1;
			   $clearsaleOrder->Customfields[1]->Name = 'avsStreetAddressResponseCode';
			   $clearsaleOrder->Customfields[1]->Value = $payment->getAdditionalInformation('avsStreetAddressResponseCode');				   
			}
			
			if($payment->getAdditionalInformation('cvvResponseCode'))
			{ 				
			   $clearsaleOrder->Customfields[1]->Type = 1;
			   $clearsaleOrder->Customfields[1]->Name = 'CVV_RESULT_CODE';
			   $clearsaleOrder->Customfields[1]->Value = $payment->getAdditionalInformation('cvvResponseCode');				   
			}
			
			$countryCode =  $billingAddress->getCountry();
			$country = Mage::getModel('directory/country')->loadByCode($countryCode);
			$countryname = $country->getName();
			
			$clearsaleOrder->BillingData = new Clearsale_Total_Model_Order_Entity_Person();
			$clearsaleOrder->BillingData->ID = "1";
			$clearsaleOrder->BillingData->Email = $email;
			$clearsaleOrder->BillingData->LegalDocument = $legalDocument;
			$clearsaleOrder->BillingData->BirthDate = $dob;
			$clearsaleOrder->BillingData->Name = $billingName;
			$clearsaleOrder->BillingData->Type = 1;  
			$clearsaleOrder->BillingData->Gender = 'M';	
			$clearsaleOrder->BillingData->Address->City = $billingAddress->getCity();
			$clearsaleOrder->BillingData->Address->Country = $countryname;
			$clearsaleOrder->BillingData->Address->Street = $billingAddress->getStreet(1);
			$clearsaleOrder->BillingData->Address->Comp = $billingAddress->getStreet(2);
			if($billingAddress->getStreet(4))
			{
			  $clearsaleOrder->BillingData->Address->County = $billingAddress->getStreet(4);	
			}
			$arr = explode(' ',trim($billingAddress->getStreetFull()));
			$clearsaleOrder->BillingData->Address->Number = $arr[0];
			if($shippingAddress->getRegion())
			{
			  $clearsaleOrder->BillingData->Address->State = $shippingAddress->getRegion();
			}else
			{
			 $clearsaleOrder->BillingData->Address->State = "**";
			}
			
			$zipcodeBilling = preg_replace('/[^0-9]/', '', $billingAddress->getPostcode());
			
			if($zipcodeBilling)
			{
			 $clearsaleOrder->BillingData->Address->ZipCode = $zipcodeBilling;
			}else
			{
			 $clearsaleOrder->BillingData->Address->ZipCode = "XXX";
			}
			
			if($location == "BRA")
			{
				$clearsaleOrder->BillingData->Phones[0] = new Clearsale_Total_Model_Order_Entity_Phone();
				$clearsaleOrder->BillingData->Phones[0]->AreaCode = substr($billingPhone, 0, 2);
				$clearsaleOrder->BillingData->Phones[0]->Number = substr($billingPhone, 2, 9);
				$clearsaleOrder->BillingData->Phones[0]->CountryCode = "55";            
				$clearsaleOrder->BillingData->Phones[0]->Type = 1;
			}else
			{
				$clearsaleOrder->BillingData->Phones[0] = new Clearsale_Total_Model_Order_Entity_Phone();
				$clearsaleOrder->BillingData->Phones[0]->AreaCode = substr($billingPhone, 0, 3);
				$clearsaleOrder->BillingData->Phones[0]->Number = $billingPhone;
				$clearsaleOrder->BillingData->Phones[0]->CountryCode = "1";            
				$clearsaleOrder->BillingData->Phones[0]->Type = 1;
			}
			
			
			
			$countryCode =  $shippingAddress->getCountry();
			$country = Mage::getModel('directory/country')->loadByCode($countryCode);
			$countryname = $country->getName();

			$clearsaleOrder->ShippingData = new Clearsale_Total_Model_Order_Entity_Person();
			$clearsaleOrder->ShippingData->ID = "1";
			$clearsaleOrder->ShippingData->Email = $email;
			$clearsaleOrder->ShippingData->LegalDocument = $legalDocument;
			$clearsaleOrder->ShippingData->BirthDate = $dob;
			$clearsaleOrder->ShippingData->Name = $shippingName;
			$clearsaleOrder->ShippingData->Gender = 'M';
			$clearsaleOrder->ShippingData->Type = 1;

			$clearsaleOrder->ShippingData->Address->City = $shippingAddress->getCity();
			$clearsaleOrder->ShippingData->Address->Country = $countryname;
			$clearsaleOrder->ShippingData->Address->Street = $shippingAddress->getStreet(1);
			$clearsaleOrder->ShippingData->Address->Comp = $shippingAddress->getStreet(2);

			if($shippingAddress->getStreet(4))
			{
			  $clearsaleOrder->ShippingData->Address->County = $shippingAddress->getStreet(4);	
			}
			$arr = explode(' ',trim($shippingAddress->getStreetFull()));
			$clearsaleOrder->ShippingData->Address->Number = $arr[0];
			
			$shippingState =  $shippingAddress->getRegion();
			
			if($shippingState)
			{
			  $clearsaleOrder->ShippingData->Address->State = $shippingState;
			}else
			{
			  $clearsaleOrder->ShippingData->Address->State = "**";
			}
			
			$zipcodeShipping = preg_replace('/[^0-9]/', '', $shippingAddress->getPostcode());
			
			if($zipcodeShipping)
			{
			 $clearsaleOrder->ShippingData->Address->ZipCode = $zipcodeShipping;
			}else
			{
			 $clearsaleOrder->ShippingData->Address->ZipCode = "XXX";
			}
			
						
			if($location == "BRA")
			{
				$clearsaleOrder->ShippingData->Phones[0] = new Clearsale_Total_Model_Order_Entity_Phone();
				$clearsaleOrder->ShippingData->Phones[0]->AreaCode = substr($shippingPhone, 0, 2);
				$clearsaleOrder->ShippingData->Phones[0]->Number = substr($shippingPhone, 2, 9);
				$clearsaleOrder->ShippingData->Phones[0]->CountryCode = "55";            
				$clearsaleOrder->ShippingData->Phones[0]->Type = 1;
			}else
			{
				$clearsaleOrder->ShippingData->Phones[0] = new Clearsale_Total_Model_Order_Entity_Phone();
				$clearsaleOrder->ShippingData->Phones[0]->AreaCode = substr($shippingPhone, 0, 3);
				$clearsaleOrder->ShippingData->Phones[0]->Number = $shippingPhone;
				$clearsaleOrder->ShippingData->Phones[0]->CountryCode = "1";            
				$clearsaleOrder->ShippingData->Phones[0]->Type = 1;
			}

			$itemIndex = 0;
			$TotalItems = 0;
			
			foreach ($items as $item) {
				$clearsaleOrder->Items[$itemIndex] = new Clearsale_Total_Model_Order_Entity_Item();
				$clearsaleOrder->Items[$itemIndex]->Price = number_format(floatval($item->getPrice()), 2, ".", "");
				$clearsaleOrder->Items[$itemIndex]->ProductId = $item->getSku();
				$clearsaleOrder->Items[$itemIndex]->ProductTitle = $item->getName();
				$clearsaleOrder->Items[$itemIndex]->Quantity = intval($item->getQtyOrdered());
				$TotalItems += $clearsaleOrder->Items[$itemIndex]->Price;
				$itemIndex++;				
			}
			
			$clearsaleOrder->TotalOrder  = $order->getGrandTotal();
			$clearsaleOrder->TotalItems = $TotalItems;
			$clearsaleOrder->TotalShipping = $order->getShippingInclTax();			
			
			$sessionID = $payment->getAdditionalInformation('clearsaleSessionID');
			
			if(empty($sessionID))
			{
				$sessionID = session_id();
			}
			
			 $clearsaleOrder->SessionID = $sessionID;
			
			return $clearsaleOrder;
			
		} catch (Exception $e) {
			$csLog = Mage::getSingleton('total/log');			
			$csLog->log($e->getMessage());		
		}
	}
	
	public function cardType($number)
	{
		$number = preg_replace('/[^\d]/','',$number);
		
		if (preg_match('/^3[47][0-9]{13}$/',$number))
		{
		//Amex
			return 5;
		}
		elseif (preg_match('/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/',$number))
		{
		//Diners
			return 1;
		}
		elseif (preg_match('/^6(?:011|5[0-9][0-9])[0-9]{12}$/',$number))
		{
		// return 'Discover';
		return 4;
		}
		elseif (preg_match('/^(?:2131|1800|35\d{3})\d{11}$/',$number))
		{
		// return 'JCB';
		return 4;
		}
		elseif (preg_match('/^5[1-5][0-9]{14}$/',$number))
		{
			//return 'MasterCard';
			return 2;
		}
		elseif (preg_match('/^4[0-9]{12}(?:[0-9]{3})?$/',$number))
		{
		// return 'Visa';
		return 3;
		}
		else
		{
			return 4;
		}
	}
		
	public function getCategoryName($product)
	{
		$categoryIds = $product->getCategoryIds();
		$categoryname = "";
		if(count($categoryIds) ){
			$firstCategoryId = $categoryIds[0];
			$_category = Mage::getModel('catalog/category')->load($firstCategoryId);

			$categoryname = $_category->getName();
		}
		return $categoryname;
	}	
	
		
	public function getCountryCode($countryName)
	{
		$countryId = '';
		$countryCollection = Mage::getModel('directory/country')->getCollection();
		foreach ($countryCollection as $country) {
			if ($countryName == $country->getName()) {
				$countryId = $country->getCountryId();
				break;
			}
		}
		return $countryId;
	}

	
	public function createInvoice($order)
	{
		$invoice = $order->prepareInvoice()
		->setTransactionId($order->getId())
		->addComment("Invoice auto created by Clearsale approvement configuration.")
		->register()
		->pay();

		$transaction_save = Mage::getModel('core/resource_transaction')
		->addObject($invoice)
		->addObject($invoice->getOrder());

		$transaction_save->save();

		$shipment = $order->prepareShipment();
		if( $shipment ) {
			$shipment->register();
			$order->setIsInProcess(true);

			$transaction_save = Mage::getModel('core/resource_transaction')
			->addObject($shipment)
			->addObject($shipment->getOrder())
			->save();
		}
	}
	
	public function getCCInfo($order) {
	
	try
	{
		$payment = $order->getPayment();
		$cc = $payment->getCcNumber();
		
		if(isset($cc))
		{		
			$payment = $order->getPayment();
			$last4 = substr($payment->getCcNumber(),(strlen($cc)-4),4);
			$bin = substr($payment->getCcNumber(),0,6);		 
			
			$number = $bin."XX-XXXXX-".$last4;			
			$payment->setAdditionalInformation('clearsaleCCBin', $bin);
			$payment->setAdditionalInformation('clearsaleCCLast4', $last4);
			$payment->setAdditionalInformation('clearsaleCCNumber', $number);
			$payment->setAdditionalInformation('clearsaleCcOwner', $payment->getCcOwner());
	
		}
		
	} 
	catch (Exception $e) {

		$csLog = Mage::getSingleton('total/log');			
		$csLog->log($e->getMessage());		
			
	}
    }

    public function sendSpecificOrder($orderId)
	{
		$csLog = Mage::getSingleton('total/log');	
		try {	
			$isActive = Mage::getStoreConfig("clearsale_total/general/active");

			if ($isActive) 
			{
				$csLog->log('Enviar Order ->' . $orderId);
				$order = new Mage_Sales_Model_Order(); 
				$order->loadByIncrementId($orderId);
				if(!empty($order))
				{
					$isReanalysis = false;
					$storeId = $order->getStoreId();
					$payment = $order->getPayment();
					$environment = Mage::getStoreConfig("clearsale_total/general/environment",$storeId);
					$analysisLocation = Mage::getStoreConfig("clearsale_total/general/analysislocation",$storeId);
					$CreditcardMethods = explode(",", Mage::getStoreConfig("clearsale_total/general/credicardmethod"));
					$authBO = Mage::getModel('total/auth_business_object');
					$authResponse = $authBO->login($environment);			
					$clearSaleOrder = $this->toClearsaleOrderObject($order,$isReanalysis,$analysisLocation);			 
					$requestOrder = new Clearsale_Total_Model_Order_Entity_RequestOrder();
					$requestOrder->ApiKey = Mage::getStoreConfig("clearsale_total/general/key",$storeId);
					$requestOrder->LoginToken = $authResponse->Token->Value;
					$requestOrder->AnalysisLocation = $analysisLocation;
					$requestOrder->Orders[0] = $clearSaleOrder;			  
					
					$orderBO = Mage::getModel('total/order_business_object');
																			
					$response = $orderBO->send($requestOrder,$environment);
					
					$csLog->log('<br />Enviar Order ->' . $orderId . ' OK ');
					return $response;			
				}
			}			
		} 
		catch (Exception $e) {
                    
					
			$csLog->log($e->getMessage());
		}
		
		
	}
	
	public function setAsSent($orderID)
	{
		require_once('app/Mage.php');
		Mage::app();
		$order = new Mage_Sales_Model_Order();			
		$order->loadByIncrementId($orderID);
		$payment = $order->getPayment();
		$payment->setAdditionalInformation('clearsale', 'ok');		
	}
			
    			
	public function getSpecificOrder($orderID)
	{
		require_once('app/Mage.php');
		Mage::app();
		
		$isActive = Mage::getStoreConfig("clearsale_total/general/active");

		
		if ($isActive) 
		{			
			$order = new Mage_Sales_Model_Order();			
			$order->loadByIncrementId($orderID);
			
			if($order)
			{
			 $storeId = $order->getStoreId();
			 
			 $environment = Mage::getStoreConfig("clearsale_total/general/environment",$storeId);
			 $analysisLocation = Mage::getStoreConfig("clearsale_total/general/analysislocation",$storeId);
			 $authBO = Mage::getModel('total/auth_business_object');
			 $authResponse = $authBO->login($environment);
			 $orderBO = Mage::getModel('total/order_business_object');				
			 
			 	if($authResponse)
			 	{		
			 		$requestOrder =  Mage::getModel('total/order_entity_requestorder');
			 		$requestOrder->ApiKey =  Mage::getStoreConfig("clearsale_total/general/key",$storeId);
			 		$requestOrder->LoginToken = $authResponse->Token->Value;
			 		$requestOrder->AnalysisLocation = $analysisLocation;
			 		$requestOrder->Orders = array();
			 		$requestOrder->Orders[0] = $orderID;			
			 		$ResponseOrder = $orderBO->get($requestOrder,$environment);					
			 		$orderBO->Update($ResponseOrder->Orders[0]);									
			 	}			 		
			}
		}
	}
	
	public function orderExists($orderID)
	{
		try {
		require_once('app/Mage.php');
		Mage::app();
		
		$csLog = Mage::getSingleton('total/log');
		
		$isActive = Mage::getStoreConfig("clearsale_total/general/active");
		$return = true;
					
			$order = new Mage_Sales_Model_Order();			
			$order->loadByIncrementId($orderID);
			
		if($order)
		{
			$csStatus = array('approved_clearsale','reproved_clearsale','canceled_clearsale','analysing_clearsale','holded','complete','canceled','closed');
			
			$payment = $order->getPayment();
			
			$history = $order->getStatusHistoryCollection();
			
			if($history)
			{
				foreach($history as $comment)
				{
					$csLog->log('HistoryStatus -> ' .$comment->getStatus());
					
					if (in_array($comment->getStatus(),$csStatus))
					{
						return true;
					}
				}				
			}
			
			return false;
		}
		
		return $return;
		}
		catch (Exception $e) {                    		
			$csLog->log('OrderExistsFunction: '.$e->getMessage());
			return false;
		}
	}
	


	
	public function sendPendingOrders()
	{
		require_once('app/Mage.php');
		Mage::app();
		
		$isActive = Mage::getStoreConfig("clearsale_total/general/active");
			
			
		if ($isActive) 
		{	
			$csLog = Mage::getSingleton('total/log');			
			$orderBO = Mage::getModel('total/order_business_object');	
			
			$time = time();
			$to = date('Y-m-d H:i:s', $time);
			$lastTime = $time - 86400; // 60*60*24
            $from = date('Y-m-d H:i:s', $lastTime);
		
			$orders = Mage::getModel('sales/order')->getCollection()
			->addFieldToFilter('status', 'pending_clearsale');
						
			if($orders)
			{
				foreach ($orders as $order) 
				{
                    try
					{	
						$orderId =  $order->getRealOrderId();					
						$response = $this->sendSpecificOrder($orderId);
						
						
						$csLog->log('Response: ' .$response);
						
						if($response->HttpCode == 200)
						{
							$orderResponse = json_decode($response->Body);
						
							if($orderResponse)
							{
								if(!empty($orderResponse->Orders))
									 $orderBO->Update($orderResponse->Orders[0]);				    
							}
						}else if(strpos(strtolower($response->Body),"exists"))
						{
						 $csLog->log('atualizar pedido');
					     $orderStatus = new Clearsale_Total_Model_Order_Entity_Status();
						 $orderStatus->ID = $orderId;
						 $orderStatus->Status = 'AMA';
						 $orderBO->Update($orderStatus);
						 $csLog->log('atualizar pedido - OK');	
						}
					}
					catch (Exception $e) {                    		
						$csLog->log($e->getMessage());
					}
				}	
			}
		}
	}
	

 }
