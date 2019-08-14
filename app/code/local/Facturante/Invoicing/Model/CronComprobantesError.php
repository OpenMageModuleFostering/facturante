<?php
/**
 * @category    Facturante
 * @package     Invoicing
 */
class Facturante_Invoicing_Model_CronComprobantesError {
    /**
     * Este cron busca las ordenes con error en comprobante y corre el generateinvoicefororder nuevamente.
     */
    public function callGenerateInvoiceForOrder() {
        /** Tomo las ordenes con estado general de error */
        $orders = Mage::getModel('sales/order')->getCollection();
        $orders->addFilter('facturante_invoice_status','Error en Comprobante');

        foreach ($orders as $order) {
            /** @var  $cantidadComprobantesError
             * Por cada orden tomo la cantidad de comprobantes con error para luego evaluar si corresponde o no hacer una nueva peticion a la api.
             */
            $cantidadComprobantesError = Mage::getModel('facturante_invoicing/order_invoicingstatus')->getCollection()
                ->addFilter('order_increment_id', $order->getIncrementId())
                ->addFieldToFilter('status', array('in' => array('Error en Comprobante')))
                ->setOrder('idcomprobante')
                ->count();

            /** Si la consulta anterior nos dice que hay comprobantes con errores, entonces procedemos a analizar */
            if($cantidadComprobantesError > 0) {
                /** Si tiene menos de 10 errores entonces puedo peticionar nuevamente a la api. */
                if($cantidadComprobantesError < 10) {
                    Mage::helper('facturante_invoicing')->generateInvoiceForOrder($order, Facturante_Invoicing_Helper_Data::FACTURANTE_CODIGO_FACTURA);
                }
                /** Caso contrario genero un nuevo registro en la tabla indicando que se ha llegado al limite de reintentos posibles */
                else if ($cantidadComprobantesError == 10) {
                    /** @var  $checkInformado
                     * Si el count me devuelve '0' significa que todavia no le informe que se llego al limite de reintentos.
                     */
                    $checkInformado = Mage::getModel('facturante_invoicing/order_invoicingstatus')->getCollection()
                        ->addFilter('order_increment_id', $order->getIncrementId())
                        ->addFieldToFilter('status', array('in' => array('No Generado')))
                        ->count();
                    /** Aca me fijo si ya le avise que se llego al limite de reintentos. */
                    if($checkInformado == 0) {
                        $newInvoicingStatus = Mage::getModel('facturante_invoicing/order_invoicingstatus');
                        $newInvoicingStatus->setOrderId($order->getId());
                        $newInvoicingStatus->setOrderIncrementId($order->getIncrementId());
                        $newInvoicingStatus->setStatus('No Generado');
                        $newInvoicingStatus->setComments('Se ha alcanzado un limite de 10 errores para este comprobante. Por favor contacte a la mesa de ayuda de Facturante al (011) 5199-2087');
                        $newInvoicingStatus->setUpdateDate(now());
                        $newInvoicingStatus->save();
                    }
                }
            }
        }
    }
}