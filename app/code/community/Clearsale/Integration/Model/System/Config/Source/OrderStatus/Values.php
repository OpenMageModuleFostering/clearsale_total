<?php

class Clearsale_Total_Model_System_Config_Source_OrderStatus_Values
{
     public function toOptionArray() {

       $list = array(array('value'=>'', 'label'=>Mage::helper('adminhtml')->__('--Please Select--')));
       $orderStatusCollection = Mage::getModel('sales/order_status')->getResourceCollection()->getData();
 
		foreach($orderStatusCollection as $orderStatus) {

			$title = $orderStatus['label'];
			$value = $orderStatus['status'];
			
			 $list[$value] = array(
						'label'   => $title,
						'value' => $value,
					);			
		}

        return $list;
	}
}

