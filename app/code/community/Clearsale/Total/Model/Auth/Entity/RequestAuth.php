<?php

class ClearSale_Total_Model_Auth_Entity_RequestAuth
{
	public $Login;
	function __construct() {
		$this->Login = new ClearSale_Total_Model_Auth_Entity_Credentials();
	}
}