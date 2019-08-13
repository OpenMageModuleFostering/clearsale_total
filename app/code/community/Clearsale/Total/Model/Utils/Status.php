<?php

class ClearSale_Total_Model_Utils_Status
{
public function toMagentoStatus($clearSaleStatus)
	{
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
			break;
		}
		
		return $status;
	}
}
	 