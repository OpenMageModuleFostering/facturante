<?php

class Facturante_Invoicing_Model_System_Config_Source_Dropdown_Orderstatus
{
    protected $_orderStatuses;

    public function toOptionArray()
    {
        $statuses = Mage::getModel('sales/order_config')->getStatuses();

        $this->_orderStatuses = array();
        $statusesCount = 0;

        foreach ($statuses as $code => $label)
        {
            $this->_orderStatuses[$statusesCount]['value'] = $code;
            $this->_orderStatuses[$statusesCount]['label'] = $label;
            $statusesCount++;
        }

        return $this->_orderStatuses;
    }
}
