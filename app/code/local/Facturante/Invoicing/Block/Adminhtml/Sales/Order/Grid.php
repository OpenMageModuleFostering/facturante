<?php
/**
 * Admin order grid customization
 *
 * @category    Facturante
 * @package     Invoicing
 */
class Facturante_Invoicing_Block_Adminhtml_Sales_Order_Grid extends Mage_Adminhtml_Block_Sales_Order_Grid
{
    /**
    * Add new column to sales order grid
    */
    protected function _prepareColumns()
    {
        parent::_prepareColumns();

        $this->addColumnAfter('facturante_invoice_status', array(
            'header'=> Mage::helper('sales')->__('Estado Facturación'),
            'width' => '80px',
            'type'  => 'text',
            'index' => 'increment_id',
            'filter' => false,
            'renderer'  => 'facturante_invoicing/adminhtml_sales_order_renderer_afipInvoiceStatus'
        ), 'status');

        $this->sortColumnsByOrder();

        return $this;
    }

    /**
     * Add Generar Factura AFIP mass action
     */
    protected function _prepareMassaction()
    {
        parent::_prepareMassaction();

        $this->getMassactionBlock()->addItem('mass_generate_invoice', array(
            'label'=> Mage::helper('sales')->__('Generar Factura AFIP'),
            'url'  => $this->getUrl('*/facturante/massInvoice'),
        ));

        return $this;
    }

}