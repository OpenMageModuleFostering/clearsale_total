<?php

class Clearsale_Total_Model_Utils_Status
{
public function toMagentoStatus($clearSaleStatus)
	{
		$status = '';
		switch($clearSaleStatus)
		{
		case "NVO" :
		case "AMA" : $status = "analysing_clearsale";break;
		case "RPM" :
		case "SUS" :
		case "FRD" :
		case "RPA" :
		case "RPP" : $status = "reproved_clearsale";break;
		case "APM" :
		case "APA" : $status = "approved_clearsale";break;
		case "CAN" : $status = "canceled_clearsale";break;
		case "PED" : $status = "pending_clearsale";break;
			break;
		}
		
		return $status;
	}
	
public function toClearSaleStatus($magentoStatus)
	{
		$status = "";
		
		switch($magentoStatus)
		{
			case "shipped"	:
			case "closed"	:
			case "invoiced" :
			case "complete" : $status = "APM";break;
			case "canceled" : $status = "CAN";break;
			case "fraud" : $status = "RPM";break;
		}
		
		return $status;
	}
}
	 