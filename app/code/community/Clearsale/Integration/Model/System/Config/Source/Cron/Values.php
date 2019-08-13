<?php

class Clearsale_Total_Model_System_Config_Source_Cron_Values
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => '*/5 * * * *',
                'label' => '05 minutes',
            ),
            array(
                'value' => '*/10 * * * *',
                'label' => '10 minutes',
            ),
	     array(
                'value' => '*/15 * * * *',
                'label' => '15 minutes',
            ),
	     array(
                'value' => '*/30 * * * *',
                'label' => '30 minutes',
            ),
	     array(
                'value' => '*/60 * * * *',
                'label' => '60 minutes',
            ),
        );
    }
}