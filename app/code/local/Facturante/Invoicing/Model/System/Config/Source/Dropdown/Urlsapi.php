<?php

class Facturante_Invoicing_Model_System_Config_Source_Dropdown_Urlsapi
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => 'http://testing.facturante.com/api/comprobantes.svc?wsdl',
                'label' => 'http://testing.facturante.com/api/comprobantes.svc?wsdl',
            ),
            array(
                'value' => 'http://facturante.com/api/comprobantes.svc?wsdl',
                'label' => 'http://facturante.com/api/comprobantes.svc?wsdl',
            )
        );
    }
}
