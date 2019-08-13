<?php
class Clearsale_Total_Model_Order_Entity_ResponseOrder
{
	public $Orders;
	public $TransactionID;
	function __construct() {
		$this->Orders = array();
	}
}

class Clearsale_Total_Model_Order_Entity_OrderStatus
{
 public $ID;
 public $Status;
 public $Score;
}


