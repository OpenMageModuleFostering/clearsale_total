<?php

class ClearSale_Total_Model_Auth_Entity_ResponseAuth
{
	public $Token;
	function __construct() {
		$this->Token = new ClearSale_Total_Model_Auth_Entity_Token();
	}
}
