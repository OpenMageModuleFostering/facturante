<?php

class Facturante_Invoicing_Model_System_Config_Source_Dropdown_Values
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => '',
                'label' => '',
            ),
            array(
                'value' => '1',
                'label' => 'Bienes',
            ),
            array(
                'value' => '2',
                'label' => 'Servicios',
            ),
            array(
                'value' => '3',
                'label' => 'Productos y Servicios',
            )
        );
    }
}
