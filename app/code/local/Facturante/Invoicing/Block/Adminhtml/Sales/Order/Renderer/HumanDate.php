<?php
/**
 * Admin order column renderer
 *
 * @category    Facturante
 * @package     Invoicing
 */
class Facturante_Invoicing_Block_Adminhtml_Sales_Order_Renderer_HumanDate extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract 
{
    public function render(Varien_Object $row)
    {
        $fechaInvoicingStatus =  $row->getData($this->getColumn()->getIndex());
        $date = new DateTime($fechaInvoicingStatus);
        $humanDate = $date->format('d/m/Y H:i:s');
        return $humanDate;
    }
}