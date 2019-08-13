<?php

class Clearsale_Total_Model_Observer
{
	
	public function sendOrder()
	{
		try {	
			$isActive = Mage::getStoreConfig("clearsale_total/general/active");

			if ($isActive) 
			{
				
				$order = new Mage_Sales_Model_Order();
				$incrementId = Mage::getSingleton('checkout/session')->getLastRealOrderId(); 
				$order->loadByIncrementId($incrementId);
				$isReanalysis = false;
				$payment = $order->getPayment();
				$environment = Mage::getStoreConfig("clearsale_total/general/environment");
				$CreditcardMethods = explode(",", Mage::getStoreConfig("clearsale_total/general/credicardmethod"));

				if (in_array($payment->getMethodInstance()->getCode(), $CreditcardMethods))
				{
					$authBO = Mage::getModel('total/auth_business_object');
					$authResponse = $authBO->login($environment);			
					$clearSaleOrder = $this->toClearsaleOrderObject($order,$isReanalysis,$environment);			 
					$requestOrder = new Clearsale_Total_Model_Order_Entity_RequestOrder();
					$requestOrder->ApiKey = Mage::getStoreConfig("clearsale_total/general/key");
					$requestOrder->LoginToken = $authResponse->Token->Value;
					$requestOrder->AnalysisLocation = "USA";
					$requestOrder->Orders[0] = $clearSaleOrder;			  
					
					$orderBO = Mage::getModel('total/order_business_object');
					$orderResponse = $orderBO->send($requestOrder,$environment);
					
					if($orderResponse)
					{
						if($orderResponse->Orders)
						{
							$orderBO->save($orderResponse->Orders[0]);
						}
					}		
				}
			}			
		} 
		catch (Exception $e) {

			$csLog = Mage::getSingleton('total/log');			
			$csLog->log($e->getMessage());		
			
		}
		
		
	}
	
		public function sendHistoricalOrders()
	{
		try {	
		
			$orders = Mage::getModel('sales/order')->getCollection();
			
			foreach($orders as $order)
			{	
				$isReanalysis = false;
				$payment = $order->getPayment();
				$environment = Mage::getStoreConfig("clearsale_total/general/environment");
				$CreditcardMethods = explode(",", Mage::getStoreConfig("clearsale_total/general/credicardmethod"));

				if (in_array($payment->getMethodInstance()->getCode(), $CreditcardMethods))
				{
					$authBO = Mage::getModel('total/auth_business_object');
					$authResponse = $authBO->login($environment);			
					$clearSaleOrder = $this->toClearsaleOrderObject2($order,$isReanalysis,$environment,"History");			 
					$requestOrder = new Clearsale_Total_Model_Order_Entity_RequestOrder();
					$requestOrder->ApiKey = Mage::getStoreConfig("clearsale_total/general/key");
					$requestOrder->LoginToken = $authResponse->Token->Value;
					$requestOrder->AnalysisLocation = "USA";
					$requestOrder->Orders[0] = $clearSaleOrder;			  
					
					$orderBO = Mage::getModel('total/order_business_object');
					$orderResponse = $orderBO->send($requestOrder,$environment);	
					echo "Order n:".$order->getRealOrderId()." sent <br />";					
				}
			}
		} 
		catch (Exception $e) {

			$csLog = Mage::getSingleton('total/log');			
			$csLog->log($e->getMessage());		
			
		}
		
		
	}
	
	
	public function toClearsaleOrderObject2(Mage_Sales_Model_Order  $order,$isReanalysis,$location,$obs){
		
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
			$clearsaleOrder->IP = Mage::helper('core/http')->getRemoteAddr();
			$clearsaleOrder->Currency = $currency;
			$clearsaleOrder->Date = $date;
			$clearsaleOrder->Reanalysis = $isReanalysis;
			$clearsaleOrder->Email = $email;
			$clearsaleOrder->TotalOrder = number_format(floatval($order->getGrandTotal()), 2, ".", "");
			
			$StatusHandle = new Clearsale_Total_Model_Utils_Status();
			$statusCS = $StatusHandle->toClearSaleStatus($order->getStatus());
			$clearsaleOrder->Status =  $statusCS;
			echo "Status :".$statusCS."<br />";
			
			if($obs != "")
			{
			  $clearsaleOrder->Obs = $obs;	
			}
			
			$items = $order->getAllItems();
			$payment = $order->getPayment();
			
			$billingAddress = $order->getBillingAddress();
			$shippingAddress = $order->getShippingAddress();
			$dob = $customer->getDob();
			$dob = $date;
			
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
			
			if ($payment->getMethodInstance()->getCode() == "authorizenet")
			{

				if (in_array($payment->getMethodInstance()->getCode(), $creditcardMethods)) {
					
					$clearsaleOrder->Payments[$paymentIndex]->Type = 1;	
					
					$creditcardBrand = 4;
					$paymentData = $payment->getData("additional_data");

					if (strripos($paymentData, "diners") !== false)
					$creditcardBrand = 1;

					if (strripos($paymentData, "mastercard") !== false)
					$creditcardBrand = 2;

					if (strripos($paymentData, "visa") !== false)
					$creditcardBrand = 3;

					if ((strripos($paymentData, "amex") !== false) || (strripos($paymentData, "american express") !== false))
					$creditcardBrand = 5;

					if (strripos($paymentData, "hipercard") !== false)
					$creditcardBrand = 6;

					if (strripos($paymentData, "aura") !== false)
					$creditcardBrand = 7;

					if (strripos($paymentData, "carrefour") !== false)
					$creditcardBrand = 8;								
				
				 $clearsaleOrder->Payments[$paymentIndex]->CardBin =  $payment->getAdditionalInformation('clearsaleCCBin');
				 $clearsaleOrder->Payments[$paymentIndex]->CardHolderName = $payment->getCcOwner();				
				 $clearsaleOrder->Payments[$paymentIndex]->CardType = $creditcardBrand;	
				 $clearsaleOrder->Payments[$paymentIndex]->CardEndNumber = $payment->getCcLast4();
				 $clearsaleOrder->Payments[$paymentIndex]->PaymentTypeID = 1;
				 
				}
			}
			

			$clearsaleOrder->BillingData = new Clearsale_Total_Model_Order_Entity_Person();
			$clearsaleOrder->BillingData->ID = "1";
			$clearsaleOrder->BillingData->Email = $email;
			$clearsaleOrder->BillingData->BirthDate = $dob;
			$clearsaleOrder->BillingData->Name = $billingName;
			$clearsaleOrder->BillingData->Type = 1;  
			$clearsaleOrder->BillingData->Gender = 'M';	
			$clearsaleOrder->BillingData->Address->City = $billingAddress->getCity();
			$clearsaleOrder->BillingData->Address->County = $billingAddress->getStreetFull();
			$clearsaleOrder->BillingData->Address->Street = $billingAddress->getStreet(1);
			$clearsaleOrder->BillingData->Address->Number = $billingAddress->getStreet(2);
			$clearsaleOrder->BillingData->Address->State = $shippingAddress->getRegion();
			$clearsaleOrder->BillingData->Address->ZipCode = preg_replace('/[^0-9]/', '', $billingAddress->getPostcode());
			$clearsaleOrder->BillingData->Phones[0] = new Clearsale_Total_Model_Order_Entity_Phone();
			$clearsaleOrder->BillingData->Phones[0]->AreaCode = substr($billingPhone, 0, 3);
			$clearsaleOrder->BillingData->Phones[0]->Number = $billingPhone;
			$clearsaleOrder->BillingData->Phones[0]->CountryCode = "1";            
			$clearsaleOrder->BillingData->Phones[0]->Type = 1;

			$clearsaleOrder->ShippingData = new Clearsale_Total_Model_Order_Entity_Person();
			$clearsaleOrder->ShippingData->ID = "1";
			$clearsaleOrder->ShippingData->Email = $email;
			$clearsaleOrder->ShippingData->LegalDocument = $legalDocument;
			$clearsaleOrder->ShippingData->BirthDate = $dob;
			$clearsaleOrder->ShippingData->Name = 'teste';
			$clearsaleOrder->ShippingData->Gender = 'M';
			$clearsaleOrder->ShippingData->Type = 1;

			$clearsaleOrder->ShippingData->Address->City = $shippingAddress->getCity();
			$clearsaleOrder->ShippingData->Address->County = $shippingAddress->getStreetFull();
			$clearsaleOrder->ShippingData->Address->Street = $shippingAddress->getStreet(1);
			$clearsaleOrder->ShippingData->Address->Number = $shippingAddress->getStreet(2);
			$clearsaleOrder->ShippingData->Address->State = $shippingAddress->getRegion();
			$clearsaleOrder->ShippingData->Address->ZipCode = preg_replace('/[^0-9]/', '', $shippingAddress->getPostcode());
			$clearsaleOrder->ShippingData->Phones[0] = new Clearsale_Total_Model_Order_Entity_Phone();
			$clearsaleOrder->ShippingData->Phones[0]->AreaCode = substr($shippingPhone, 0, 2);
			$clearsaleOrder->ShippingData->Phones[0]->CountryCode = "1";
			$clearsaleOrder->ShippingData->Phones[0]->Number = substr($shippingPhone, 2, 9);
			$clearsaleOrder->ShippingData->Phones[0]->Type = 1;

			$itemIndex = 0;
			$TotalItems = 0;
			
			foreach ($items as $item) {
				$clearsaleOrder->Items[$itemIndex] = new Clearsale_Total_Model_Order_Entity_Item();
				$clearsaleOrder->Items[$itemIndex]->Price = number_format(floatval($item->getPrice()), 2, ".", "");
				$clearsaleOrder->Items[$itemIndex]->ProductId = $item->getSku();
				$clearsaleOrder->Items[$itemIndex]->ProductTitle = $item->getName();
				$clearsaleOrder->Items[$itemIndex]->Quantity = intval($item->getQtyOrdered());
				//$clearsaleOrder->Items[0]->Category = getCategoryName($item);
				$TotalItems += $clearsaleOrder->Items[$itemIndex]->Price;
				$itemIndex++;				
			}
			
			$clearsaleOrder->TotalOrder  = $order->getGrandTotal();
			$clearsaleOrder->TotalItems = $TotalItems;
			$clearsaleOrder->TotalShipping = $order->getShippingInclTax();
			$clearsaleOrder->SessionID = Mage::getSingleton("core/session")->getEncryptedSessionId();
			
			
			return $clearsaleOrder;
			
		} catch (Exception $e) {
			$csLog = Mage::getSingleton('total/log');			
			$csLog->log($e->getMessage());		
		}
	}
	
	
	public function getClearsaleOrderStatus()
	{
		require_once('app/Mage.php');
		Mage::app();
		
		$isActive = Mage::getStoreConfig("clearsale_total/general/active");

		
		if ($isActive) 
		{
			
			$orders = Mage::getModel('sales/order')->getCollection()
			->addFieldToFilter('status', 'analysing_clearsale');	
			
			if($orders)
			{
				$environment = Mage::getStoreConfig("clearsale_total/general/environment");
				$authBO = Mage::getModel('total/auth_business_object');
				$authResponse = $authBO->login($environment);
				$orderBO = Mage::getModel('total/order_business_object');
				
				if($authResponse)
				{
					foreach ($orders as $order) {
						
						$orderId = $order->getRealOrderId();		
						$requestOrder =  Mage::getModel('total/order_entity_requestorder');
						$requestOrder->ApiKey =  Mage::getStoreConfig("clearsale_total/general/key");
						$requestOrder->LoginToken = $authResponse->Token->Value;;
						$requestOrder->AnalysisLocation = "USA";//Mage::getStoreConfig("clearsale_total/general/analysislocation");
						$requestOrder->Orders = array();
						$requestOrder->Orders[0] = $orderId;			
						$ResponseOrder = $orderBO->get($requestOrder,$environment);			
						$orderBO->Update($ResponseOrder->Orders[0]);
						
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
			$clearsaleOrder->IP = Mage::helper('core/http')->getRemoteAddr();
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
			$dob = $date;
			
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
			
			//if (in_array($payment->getMethodInstance()->getCode(), $creditcardMethods))
			if($payment->getAdditionalInformation('clearsaleCCNumber'))
			{

				if (in_array($payment->getMethodInstance()->getCode(), $creditcardMethods)) {
					
					$clearsaleOrder->Payments[$paymentIndex]->Type = 1;	
					
					$creditcardBrand = 4;
					$paymentData = $payment->getData("additional_data");

					if (strripos($paymentData, "diners") !== false)
					$creditcardBrand = 1;

					if (strripos($paymentData, "mastercard") !== false)
					$creditcardBrand = 2;

					if (strripos($paymentData, "visa") !== false)
					$creditcardBrand = 3;

					if ((strripos($paymentData, "amex") !== false) || (strripos($paymentData, "american express") !== false))
					$creditcardBrand = 5;

					if (strripos($paymentData, "hipercard") !== false)
					$creditcardBrand = 6;

					if (strripos($paymentData, "aura") !== false)
					$creditcardBrand = 7;

					if (strripos($paymentData, "carrefour") !== false)
					$creditcardBrand = 8;
				
								
				 $clearsaleOrder->Payments[$paymentIndex]->CardBin =  $payment->getAdditionalInformation('clearsaleCCBin');
				 $clearsaleOrder->Payments[$paymentIndex]->CardHolderName = $payment->getCcOwner();				
				 $clearsaleOrder->Payments[$paymentIndex]->CardType = $creditcardBrand;				
				 $clearsaleOrder->Payments[$paymentIndex]->PaymentTypeID = 1;
				}
			}
			

			$clearsaleOrder->BillingData = new Clearsale_Total_Model_Order_Entity_Person();
			$clearsaleOrder->BillingData->ID = "1";
			$clearsaleOrder->BillingData->Email = $email;
			$clearsaleOrder->BillingData->BirthDate = $dob;
			//$clearsaleOrder->BillingData->LegalDocument = '11111111111';
			$clearsaleOrder->BillingData->Name = $billingName;
			$clearsaleOrder->BillingData->Type = 1;  
			$clearsaleOrder->BillingData->Gender = 'M';	
			$clearsaleOrder->BillingData->Address->City = $billingAddress->getCity();
			$clearsaleOrder->BillingData->Address->County = $billingAddress->getStreetFull();
			$clearsaleOrder->BillingData->Address->Street = $billingAddress->getStreet(1);
			$clearsaleOrder->BillingData->Address->Number = $billingAddress->getStreet(2);
			$clearsaleOrder->BillingData->Address->State = $shippingAddress->getRegion();
			$clearsaleOrder->BillingData->Address->ZipCode = preg_replace('/[^0-9]/', '', $billingAddress->getPostcode());
			$clearsaleOrder->BillingData->Phones[0] = new Clearsale_Total_Model_Order_Entity_Phone();
			$clearsaleOrder->BillingData->Phones[0]->AreaCode = substr($billingPhone, 0, 3);
			$clearsaleOrder->BillingData->Phones[0]->Number = $billingPhone;
			$clearsaleOrder->BillingData->Phones[0]->CountryCode = "1";            
			$clearsaleOrder->BillingData->Phones[0]->Type = 1;

			$clearsaleOrder->ShippingData = new Clearsale_Total_Model_Order_Entity_Person();
			$clearsaleOrder->ShippingData->ID = "1";
			$clearsaleOrder->ShippingData->Email = $email;
			$clearsaleOrder->ShippingData->LegalDocument = $legalDocument;
			$clearsaleOrder->ShippingData->BirthDate = $dob;
			$clearsaleOrder->ShippingData->Name = 'teste';
			$clearsaleOrder->ShippingData->Gender = 'M';
			$clearsaleOrder->ShippingData->Type = 1;

			$clearsaleOrder->ShippingData->Address->City = $shippingAddress->getCity();
			$clearsaleOrder->ShippingData->Address->County = $shippingAddress->getStreetFull();
			$clearsaleOrder->ShippingData->Address->Street = $shippingAddress->getStreet(1);
			$clearsaleOrder->ShippingData->Address->Number = $shippingAddress->getStreet(2);
			$clearsaleOrder->ShippingData->Address->State = $shippingAddress->getRegion();
			$clearsaleOrder->ShippingData->Address->ZipCode = preg_replace('/[^0-9]/', '', $shippingAddress->getPostcode());
			$clearsaleOrder->ShippingData->Phones[0] = new Clearsale_Total_Model_Order_Entity_Phone();
			$clearsaleOrder->ShippingData->Phones[0]->AreaCode = substr($shippingPhone, 0, 2);
			$clearsaleOrder->ShippingData->Phones[0]->CountryCode = "1";
			$clearsaleOrder->ShippingData->Phones[0]->Number = substr($shippingPhone, 2, 9);
			$clearsaleOrder->ShippingData->Phones[0]->Type = 1;

			$itemIndex = 0;
			$TotalItems = 0;
			
			foreach ($items as $item) {
				$clearsaleOrder->Items[$itemIndex] = new Clearsale_Total_Model_Order_Entity_Item();
				$clearsaleOrder->Items[$itemIndex]->Price = number_format(floatval($item->getPrice()), 2, ".", "");
				$clearsaleOrder->Items[$itemIndex]->ProductId = $item->getSku();
				$clearsaleOrder->Items[$itemIndex]->ProductTitle = $item->getName();
				$clearsaleOrder->Items[$itemIndex]->Quantity = intval($item->getQtyOrdered());
				//$clearsaleOrder->Items[0]->Category = getCategoryName($item);
				$TotalItems += $clearsaleOrder->Items[$itemIndex]->Price;
				$itemIndex++;				
			}
			
			$clearsaleOrder->TotalOrder  = $order->getGrandTotal();
			$clearsaleOrder->TotalItems = $TotalItems;
			$clearsaleOrder->TotalShipping = $order->getShippingInclTax();
			$clearsaleOrder->SessionID = Mage::getSingleton("core/session")->getEncryptedSessionId();
			
			
			return $clearsaleOrder;
			
		} catch (Exception $e) {
			$csLog = Mage::getSingleton('total/log');			
			$csLog->log($e->getMessage());		
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
}
