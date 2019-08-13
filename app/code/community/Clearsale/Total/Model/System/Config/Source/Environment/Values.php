<?php

class ClearSale_Total_Model_System_Config_Source_Environment_Values
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => 'https://integration.clearsale.com.br/',
                'label' => ' Production',
            ),
            array(
                'value' => 'https://sandbox.clearsale.com.br/',
                'label' => ' SandBox',
            ),
        );
    }
}