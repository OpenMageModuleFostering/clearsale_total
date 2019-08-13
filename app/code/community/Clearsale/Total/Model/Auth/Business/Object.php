<?php

class ClearSale_Total_Model_Auth_Business_Object
{

public $Http;

function __construct() {
		$this->Http = new ClearSale_Total_Model_Utils_HttpHelper();
	}

public function login($enviroment) {

		$url = $enviroment."api/auth/login/";
		$authRequest = new ClearSale_Total_Model_Auth_Entity_RequestAuth();
		$authRequest->Login->ApiKey = Mage::getStoreConfig("tab1/general/key");
		$authRequest->Login->ClientID = Mage::getStoreConfig("tab1/general/clientid");
		$authRequest->Login->ClientSecret =Mage::getStoreConfig("tab1/general/clientsecret");	
		$response = $this->Http->postData($authRequest, $url);	
		return $response;
	}

public function logout($enviroment) {
		$authRequest = new ClearSale_Total_Model_Auth_Entity_RequestAuth();
		$authRequest->Login->ApiKey = Mage::getStoreConfig("tab1/general/key");
		$authRequest->Login->ClientID = Mage::getStoreConfig("tab1/general/clientid");
		$authRequest->Login->ClientSecret =Mage::getStoreConfig("tab1/general/clientsecret");
		$url = $enviroment."api/auth/logout/";
		$response = $this->Http->postData($authRequest, $url);
		return $response;
	}
}
   