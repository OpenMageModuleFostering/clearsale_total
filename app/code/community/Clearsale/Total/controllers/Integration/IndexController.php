<?php

 class Clearsale_Total_Integration_IndexController extends Mage_Core_Controller_Front_Action
 {
    public function indexAction()
    {
      $this->loadLayout();
      $this->renderLayout();
    }
 }