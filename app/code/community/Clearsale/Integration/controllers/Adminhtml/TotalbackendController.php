<?php
class Clearsale_Total_Adminhtml_TotalbackendController extends Mage_Adminhtml_Controller_Action
{
	public function indexAction()
    {
		
       $this->loadLayout();
	   $this->_setActiveMenu('total/adminhtml_totalbackend');
	   $this->_title($this->__("Dashboard"));
	   $this->renderLayout();
    }
}