<?php
class ClearSale_Total_Model_Order_Entity_Order
{
	public $ID;
	public $Date;
	public $Email;       
	public $TotalItems;     
	public $TotalOrder; 
	public $TotalShipping;
	public $Currency;     
	public $Payments;        
	public $BillingData;      
	public $ShippingData;      
	public $Items;     
	public $CustomFields;     
	public $SessionID;
	public $IP;
	public $Reanalysis;
	
	function __construct() {
		$this->ShippingData = new ClearSale_Total_Model_Order_Entity_Person();
		$this->BillingData = new ClearSale_Total_Model_Order_Entity_Person();
		$this->Items = array();
		$this->Reanalysis = false;
	}
}


