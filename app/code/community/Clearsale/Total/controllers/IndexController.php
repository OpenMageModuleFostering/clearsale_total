<?php
 <?php
 class ClearSale_Total_IndexController extends Mage_Core_Controller_Front_Action
 {
    public function indexAction()
    {
      $this->loadLayout();
      $this->renderLayout();
    }
    public function somethingAction()
    {
      echo 'test nameMethod';
    }
 }