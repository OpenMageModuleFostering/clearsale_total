<?php

class Clearsale_Total_Model_Log {

  protected $_adapter;

  function __construct() {
    $this->_adapter = Mage::getModel('core/log_adapter', 'clearsale_total.log');
  }

  public function getAdapter() {
    return $this->_adapter;
  }

  public function log($message) {
    $this->getAdapter()->log($message);
  }

}