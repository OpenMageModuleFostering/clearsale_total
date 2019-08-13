<?php

class Clearsale_Total_Model_Auth_Business_Object
{

public $Http;

function __construct() {
		$this->Http = new Clearsale_Total_Model_Utils_HttpHelper();
	}

public function login($enviroment) {

		$url = $enviroment."api/auth/login/";
		$authRequest = new Clearsale_Total_Model_Auth_Entity_RequestAuth();
		$authRequest->Login->ApiKey = Mage::getStoreConfig("clearsale_total/general/key");
		$authRequest->Login->ClientID = Mage::getStoreConfig("clearsale_total/general/clientid");
		$authRequest->Login->ClientSecret =Mage::getStoreConfig("clearsale_total/general/clientsecret");	
		$response = $this->Http->postData($authRequest, $url);	
                
                $credentials = "";
                
                if($response->HttpCode == 200)
                {
                    $credentials = json_decode($response->Body);
                }
                
		return $credentials;
	}

public function logout($enviroment) {
		$authRequest = new Clearsale_Total_Model_Auth_Entity_RequestAuth();
		$authRequest->Login->ApiKey = Mage::getStoreConfig("clearsale_total/general/key");
		$authRequest->Login->ClientID = Mage::getStoreConfig("clearsale_total/general/clientid");
		$authRequest->Login->ClientSecret =Mage::getStoreConfig("clearsale_total/general/clientsecret");
		$url = $enviroment."api/auth/logout/";
		$response = $this->Http->postData($authRequest, $url);
		     
                $credentials = "";
                
                if($response->HttpCode == 200)
                {
                    $credentials = json_decode($response->Body);
                }
                
		return $credentials;
	}
}
   