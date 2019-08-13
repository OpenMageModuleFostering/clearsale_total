<?php

class ClearSale_Total_Block_Adminhtml_Sales_Order_View extends Mage_Adminhtml_Block_Sales_Order_View {
    public function  __construct() {

        parent::__construct();
	
	 //create URL to our custom action
        	
	//$url = Mage::getModel('adminhtml/url')->getUrl('total/observer/sendSpecificOrder');

        //add the button
        //$this->_addButton('cygtest_resubmit', array(
        //        'label'     => 'Reanalysis',
        //        'onclick'   => 'setLocation(\'' . $url . '\')',
        //        'class'     => 'go'
        //));


    }
}