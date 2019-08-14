<?php
/**
 * @category    Facturante
 * @package     Invoicing
 */
class Facturante_Invoicing_Model_Observer {
    protected $_orderStatusBefore;
    protected $_orderStatusBeforeLabel = '';

    public function adminhtmlWidgetContainerHtmlBefore($event) {
        $block = $event->getBlock();
        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_View) {
            $order = Mage::registry('current_order');

            // Generar factura
            $invoiceMessage = Mage::helper('facturante_invoicing')->__('¿Deseas generar una factura AFIP para esta orden?');
            $invoiceUrl = Mage::helper('adminhtml')->getUrl('*/facturante/singleAfipInvoiceGeneration',array('order_id'=>$order->getId()));
            $block->addButton('generate_afip_invoice', array(
                'label'     => Mage::helper('facturante_invoicing')->__('Generar Factura'),
                'onclick'   => "confirmSetLocation('{$invoiceMessage}','{$invoiceUrl}')",
                'class'     => 'go'
            ));

            // Generar nota de crédito
            $creditMessage = Mage::helper('facturante_invoicing')->__('¿Deseas generar una nota de crédito AFIP para esta orden?');
            $creditUrl = Mage::helper('adminhtml')->getUrl('*/facturante/singleAfipCreditGeneration',array('order_id'=>$order->getId()));
            $block->addButton('generate_afip_debit', array(
                'label'     => Mage::helper('facturante_invoicing')->__('Generar Nota de Crédito'),
                'onclick'   => "confirmSetLocation('{$creditMessage}','{$creditUrl}')",
                'class'     => 'go'
            ));

            // Generar ntoa de debito
            $debitMessage = Mage::helper('facturante_invoicing')->__('¿Deseas generar una nota de débito AFIP para esta orden?');
            $debitUrl = Mage::helper('adminhtml')->getUrl('*/facturante/singleAfipDebitGeneration',array('order_id'=>$order->getId()));
            $block->addButton('generate_afip_credit', array(
                'label'     => Mage::helper('facturante_invoicing')->__('Generar Nota de Débito'),
                'onclick'   => "confirmSetLocation('{$debitMessage}','{$debitUrl}')",
                'class'     => 'go'
            ));
        }
    }

    public function savePreviousOrderStatus($observer) {
        $order = $observer->getEvent()->getOrder();
        $this->_orderStatusBefore = $order->getOrigData('status');
    }

    public function autogenerateInvoice($observer) {

        $order = $observer->getEvent()->getOrder();
        $storeId = $order->getStoreId();

        $autoInvoicing = Mage::getStoreConfig('facturante/connection4/configuracion_activada', $storeId);

        // Si el modo autoinvoicing está activado
        if ($autoInvoicing) {
            $orderId = $order->getId();
            $newOrderStatus = $order->getData('status');
            $oldOrderStatus = $this->_orderStatusBefore;

            if (!$oldOrderStatus) {
                $oldOrderStatus = 'new-order';
            }

            $autoInvoicingStatus = Mage::getStoreConfig('facturante/connection4/estado_orden', $storeId);

            // Si el estado en la orden cambió,
            // y el estado de la orden para la autofacturación es el mismo que el estado nuevo
            if($oldOrderStatus != $newOrderStatus && $newOrderStatus == $autoInvoicingStatus) {
                // Autogenerar factura para orden
                if ($orderId) {
                    $order = Mage::getModel('sales/order')->load($order->getId());
                    $response = Mage::helper('facturante_invoicing')->generateInvoiceForOrder($order, 'factura');
                    if ($response) {
                        $config_modo_facturacion = Mage::getStoreConfig('facturante/connection5/modo_facturacion', $storeId);
                        $estadoCrearComprobante = "";
                        $comprobante = "";
                        $mensaje = "";
                        if( $config_modo_facturacion == 1 ) {
                            $estadoCrearComprobante = $response->CrearComprobanteSinImpuestosResult->Estado;
                            $mensaje = $response->CrearComprobanteSinImpuestosResult->Mensaje;
                            $comprobante = $response->CrearComprobanteSinImpuestosResult->IdComprobante;
                        }
                        if( $config_modo_facturacion == 2 ) {
                            $estadoCrearComprobante = $response->CrearComprobanteSinImpuestosMtxResult->Estado;
                            $mensaje = $response->CrearComprobanteSinImpuestosMtxResult->Mensaje;
                            $comprobante = $response->CrearComprobanteSinImpuestosMtxResult->IdComprobante;
                        }
                    }
                }
            }
        }
    }
}