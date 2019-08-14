<?php

class Facturante_Invoicing_Model_System_Config_Source_Dropdown_Modosfacturacion
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
                'label' => 'Factura Electrónica genérica AFIP RG 2485 o RG 3749',
            ),
            array(
                'value' => '2',
                'label' => 'Factura Electrónica con Detalle (Mtx o Matrix) - RG 2904',
            )
        );
    }
}
