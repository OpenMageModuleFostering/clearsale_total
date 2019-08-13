<?php
class Clearsale_Total_Model_Mysql4_Clearsaleorderdiagnostic extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init("total/clearsaleorderdiagnostic", "order_id");
    }
}