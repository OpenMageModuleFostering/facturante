<?php
/**
 * Admin order column renderer
 *
 * @category    Facturante
 * @package     Invoicing
 */
class Facturante_Invoicing_Block_Adminhtml_Sales_Order_Renderer_AfipInvoiceStatus extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    public function render(Varien_Object $row)
    {
        $orderId =  $row->getData($this->getColumn()->getIndex());
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        $afipInvoicingStatus = $order->getData('facturante_invoice_status');

        return $afipInvoicingStatus;
    }
    
}