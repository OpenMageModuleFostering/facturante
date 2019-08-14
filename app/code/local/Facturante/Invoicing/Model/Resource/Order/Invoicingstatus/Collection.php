<?php
/**
 * @category    Facturante
 * @package     Invoicing
 */
class Facturante_Invoicing_Model_Resource_Order_Invoicingstatus_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('facturante_invoicing/order_invoicingstatus');
    }
}