<?php

class Riskified_Full_Model_Authorizenet extends Mage_Paygate_Model_Authorizenet 
{

    protected function _registercard(varien_object $return, mage_sales_model_order_payment $payment)
    {
        $card=parent::_registercard($return,$payment);
        $card->setCcAvsResultCode($return->getAvsResultCode());
        $card->setCcResponseCode($return->getCardCodeResponseCode());
        $payment->setCcAvsStatus($return->getAvsResultCode());
        $payment->setCcCidStatus($return->getCardCodeResponseCode());
        return $card;
    }
}
