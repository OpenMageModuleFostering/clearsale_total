<?php

class ClearSale_Total_Model_Log {

  protected $_adapter;

  function __construct() {
    $this->_adapter = Mage::getModel('core/log_adapter', 'cs_total.log');
  }

  // It's public incase we want to use ->debug(), ->info(), and friends
  public function getAdapter() {
    return $this->_adapter;
  }

  public function log($message) {
    $this->getAdapter()->log($message);
  }

}