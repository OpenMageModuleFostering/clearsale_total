<?php

class ClearSale_Total_Model_Order_Entity_Person
{
	public $ID;      
	public $Type;
	public $Name;
	public $BirthDate;
	public $Email;
	public $LegalDocument;
	public $Gender;
	
	public $Address;
	public $Phones; 

	function __construct() {
		$this->Address = new ClearSale_Total_Model_Order_Entity_Address();
		$this->Phones = array();

	}
}


