<?php
/**
 * @category    Facturante
 * @package     Invoicing
 */
class Facturante_Invoicing_Model_CronComprobantesPendientes
{

    /**
     * Esta funcion solicita a facturante que informe el estado de cada comprobante que actualmente esta en estado 'ESPERANDO CAE'. Si el estado ha cambiado, entonces lo actualiza.
     */
    public function actualizarEstadoComprobantes()
    {
        $allStores = Mage::app()->getStores();
        foreach ($allStores as $eachStoreId => $val)
        {
            $storeId = Mage::app()->getStore($eachStoreId)->getId();

            $usernameValue = Mage::getStoreConfig('facturante/connection/username', $storeId);
            $passwordValue = Mage::getStoreConfig('facturante/connection/password', $storeId);
            $businessIdValue = Mage::getStoreConfig('facturante/connection/business_id', $storeId);

            $auth = array(
                'Empresa' => $businessIdValue,
                'Hash' => $passwordValue,
                'Usuario' => $usernameValue
            );
            /** Si aún no se ha configurado la url, entonces seteo la de testing. De ese modo permite ingresar al system config y cambiarla. */
            $urlApi = Mage::getStoreConfig('facturante/connection/api_url', $storeId);
            if( empty( $urlApi ) ) {
                $urlApi = "http://testing.facturante.com/api/comprobantes.svc?wsdl";
            }
            $client = new SoapClient( $urlApi );

            $orders = Mage::getModel('sales/order')->getCollection()
                        ->addFieldToFilter('facturante_invoice_status', array('nin' => array('Procesado')));

            foreach ($orders as $order)
            {
                /** Acá tomo el estado de su última peticion de detalle de comprobante y luego actualizo el estado general
                 *  de la orden */
                $comprobantesOrder = Mage::getModel('facturante_invoicing/order_invoicingstatus')->getCollection()->addFilter('order_increment_id', $order->getIncrementId())->addFieldToFilter('status', array('nin' => array('Procesado', 'Error en Envío', 'No Generado')))->addFieldToSelect('idcomprobante')->addFieldToSelect('status')->setOrder('update_date', 'DESC')->load();
                /** Tomo la ultima actualizacion de comprobante  */
                foreach ($comprobantesOrder as $comprobante)
                {
                    $idComprobante = $comprobante->idcomprobante;
                    $estadoAnteriorComprobante = $comprobante->status;
                    $responseDetalleComprobante = Mage::helper('facturante_invoicing')->getDetalleComprobante($auth, $idComprobante, $client);
                    $estadoActualComprobante = Mage::helper('facturante_invoicing')->getStringEstadoComprobante($responseDetalleComprobante->DetalleComprobanteResult->Comprobante->EstadoComprobante);
                    /** Si el estado del comprobante ha cambiado, entonces guardo el registro en la tabla de invoicingstatus */
                    if (((string)$estadoAnteriorComprobante !== (string)$estadoActualComprobante) && (!empty($estadoActualComprobante)))
                    {
                        $newComprobanteStatus = Mage::getModel('facturante_invoicing/order_invoicingstatus');
                        $newComprobanteStatus->setOrderId($order->getId());
                        $newComprobanteStatus->setOrderIncrementId($order->getIncrementId());
                        $newComprobanteStatus->setIdcomprobante($idComprobante);
                        $newComprobanteStatus->setUpdateDate(now());
                        $newComprobanteStatus->setStatus($estadoActualComprobante);
                        switch ($estadoActualComprobante)
                        {
                            case 'Error en Comprobante':
                                $newComprobanteStatus->setComments($responseDetalleComprobante->DetalleComprobanteResult->Comprobante->MensajeAFIP);
                                break;
                            case 'Error en Envío':
                                $newComprobanteStatus->setComments("Comprobante generado con éxito y enviado, pero no ha llegado al destinatario. Puede que el email sea incorrecto, que la casilla destino no esté disponible o que esté llena.");
                                break;
                            case 'Procesado':
                                $newComprobanteStatus->setComments("Se realizó el envío al cliente con éxito.");
                                $newComprobanteStatus->setLink($responseDetalleComprobante->DetalleComprobanteResult->Comprobante->URLPDF);
                                break;
                            default:
                                break;
                        }
                        $newComprobanteStatus->save();
                        /** Acá analizo si el cambio de estado de comprobante amerita un cambio de estado a nivel global de la orden. */
                        $estadoNuevoOrden = Mage::helper('facturante_invoicing')->getStringEstadoOrden($estadoActualComprobante);
                        $orderObject = Mage::getModel('sales/order')->load($order->getId());
                        $orderObject->setFacturanteInvoiceStatus($estadoNuevoOrden);
                        $orderObject->save();
                    }
                    /** Solo trabajo con el ultimo registro y estado de cada comprobante. */
                    break;
                    /** Por eso luego del analisis hago el corte. */
                }
            }
        }
    }
}