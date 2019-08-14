<?php
/**
 * Admin grid from order tab customization
 *
 * @category    Facturante
 * @package     Invoicing
 */
class Facturante_Invoicing_Block_Adminhtml_Sales_Order_Gridfacturante extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('grid');
        $this->setSaveParametersInSession(true);
        $this->setDefaultSort('update_date');
        $this->setDefaultDir('desc');
    }

    protected function _prepareCollection()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $collection = Mage::getModel('facturante_invoicing/order_invoicingstatus')->getCollection();
        $collection->addFieldToFilter(
                'order_id', array('attribute' => 'order_id', 'eq' => $orderId)
        );
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
    protected function _prepareColumns()
    {
        parent::_prepareColumns();

        $this->addColumn('update_date', array(
            'header' => 'Fecha',
            'align' =>'right',
            'width' => '100px',
            'index' => 'update_date',
            'renderer' => 'Facturante_Invoicing_Block_Adminhtml_Sales_Order_Renderer_HumanDate',
        ));
        $this->addColumn('idcomprobante', array(
            'header' => 'Comprobante ID',
            'align' =>'right',
            'width' => '30px',
            'index' => 'idcomprobante',
        ));
        $this->addColumn('status', array(
            'header' => 'Estado',
            'align' =>'right',
            'width' => '150px',
            'index' => 'status',
        ));
        $this->addColumn('comments', array(
            'header' => 'Comentarios',
            'align' =>'right',
            'width' => '150px',
            'index' => 'comments',
        ));
        $this->addColumn('action', array(
            'header' => 'Acciones',
            'align' =>'right',
            'width' => '50px',
            'field' => 'id',
            'type'     => 'action',
            'renderer'  => 'facturante_invoicing/adminhtml_sales_order_renderer_linkView'
        ));

        return $this;
    }
}