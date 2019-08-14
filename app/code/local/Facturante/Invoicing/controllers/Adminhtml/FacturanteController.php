<?php
/**
 * Facturante admin orders page controller
 *
 * @category    Facturante
 * @package     Invoicing
 */

class Facturante_Invoicing_Adminhtml_FacturanteController extends Mage_Adminhtml_Controller_Action
{
    public function massInvoiceAction() {
        $request = $this->getRequest();
        $orderIds = $request->getParam('order_ids');

        if(!is_array($orderIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select order(s).'));

        } else {
            try {
                foreach ($orderIds as $orderId) {
                    self::commonOrderProcess($orderId, Facturante_Invoicing_Helper_Data::FACTURANTE_CODIGO_FACTURA);
                }
            } catch (Exception $e) {
                self::getErrorResponse($e);
            }
        }
        $this->_redirect('*/sales_order/index');
    }
    
    public function singleAfipInvoiceGenerationAction() {
        $orderId = $this->getRequest()->getParam('order_id');
        try {
            self::commonOrderProcess($orderId, Facturante_Invoicing_Helper_Data::FACTURANTE_CODIGO_FACTURA);
        } catch (Exception $e) {
            self::getErrorResponse($e);
        }
        $this->_redirect('*/sales_order/view/order_id/'.$orderId);
    }

    public function singleAfipCreditGenerationAction()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        try {
            self::commonOrderProcess($orderId, Facturante_Invoicing_Helper_Data::FACTURANTE_CODIGO_NOTA_DE_CREDITO);
        } catch (Exception $e) {
            self::getErrorResponse($e);
        }
        $this->_redirect('*/sales_order/view/order_id/'.$orderId);
    }

    public function singleAfipDebitGenerationAction()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        try {
            self::commonOrderProcess($orderId, Facturante_Invoicing_Helper_Data::FACTURANTE_CODIGO_NOTA_DE_DEBITO);
        } catch (Exception $e) {
            self::getErrorResponse($e);
        }
        $this->_redirect('*/sales_order/view/order_id/'.$orderId);
    }

    /** Analiza el error y devuelve el mensaje */
    public function getErrorResponse($e) {
        if($e->getMessage() == "Could not connect to host") {
            Mage::getSingleton('adminhtml/session')->addError("En estos momentos no es posible procesar su solicitud. Verifique su conexión a internet y reintente más tarde.");
        } else {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
    }

    /**
     * Procesar orden a traves de la API de facturante
     * Posibles valores para $type: 'factura', 'debito' o 'credito'
     *
     * @param int $ordenId
     * @param string $type
     */
    public function commonOrderProcess($ordenId, $type) {

        $order = Mage::getModel('sales/order')->load($ordenId);
        $storeId = $order->getStoreId();

        // Validar primero que la configuración necesaria desde los system config existe
        $prefijo = Mage::getStoreConfig('facturante/connection3/prefijo', $storeId);
        $tipoFacturacion = Mage::getStoreConfig('facturante/connection3/bienes', $storeId);

        if (!$prefijo) {
            Mage::getSingleton('core/session')->addError('Por favor, configure el prefijo AFIP en: Sistema -> Configuración -> Facturante: Prefijo');
            return false;
        }
        if (!$tipoFacturacion) {
            Mage::getSingleton('core/session')->addError('Por favor, configure el tipo de facturación AFIP en: Sistema -> Configuración -> Facturante: Tipo de Facturación');
            return false;
        }

        if ($order->getId()) {
            $response = Mage::helper('facturante_invoicing')->generateInvoiceForOrder($order, $type);

            if ($response) {
                /**
                 *  ----- Funcionalidad GTIN -----
                 *
                 * @var  $config_modo_facturacion
                 *
                 * '' (vacio): No se ha seteado el dato en el sistem config
                 * 1: Factura Electrónica Genérica  -- AFIP RG 2485 o RG 3749
                 * 2: Factura Electrónica con Detalle -- (Mtx o Matrix) - RG 2904
                 *
                 */
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

                if($estadoCrearComprobante == 'OK') {
                    Mage::getSingleton('core/session')->addSuccess('Comprobante Nº'.$comprobante.' generado correctamente para la orden Nº '.$order->getIncrementId().'.');
                } else {
                    Mage::getSingleton('core/session')->addError('Ha ocurrido un error al intentar generar el comprobante para la orden Nª '.$order->getIncrementId().'.<br>
                    Detalle: '.$mensaje);
                }
            }
        }
    }
}