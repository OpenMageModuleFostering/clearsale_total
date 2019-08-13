<?php

 class Clearsale_Total_IndexController extends Mage_Core_Controller_Front_Action
 {
    public function indexAction()
    {
       //Get current layout state
        $this->loadLayout();          
 
        $block = $this->getLayout()->createBlock(
            'Mage_Core_Block_Template',
            'total',
            array('template' => 'total/getorderstatus.phtml')
        );
 		
        $this->getLayout()->getBlock('root')->setTemplate('page/1column.phtml');
        $this->getLayout()->getBlock('content')->append($block);
        $this->_initLayoutMessages('core/session'); 
        $this->renderLayout();
    }
 }