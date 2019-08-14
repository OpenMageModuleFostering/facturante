<?php
/**
 * Admin order column renderer
 *
 * @category    Facturante
 * @package     Invoicing
 */
class Facturante_Invoicing_Block_Adminhtml_Sales_Order_Renderer_LinkView extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $link = "";
        /** Tomo la info del row actual para despues hacer una busqueda en la base  */
        $estado = $row->getData('status');
        $comprobanteId = $row->getData('idcomprobante');
        $orderId = $row->getData('order_id');

        if($estado == "Procesado")
        {
            /** Con la info recogida, filtro la base y tomo el respectivo enlace al archivo si lo hubiere */
            $collection = Mage::getModel('facturante_invoicing/order_invoicingstatus')->getCollection();
            $collection->addFieldToFilter('order_id', array('attribute' => 'order_id', 'eq' => $orderId));
            $collection->addFieldToFilter('idcomprobante', array('attribute' => 'order_id', 'eq' => $comprobanteId));
            $collection->addFieldToFilter('status', array('attribute' => 'order_id', 'eq' => 'Procesado'));
            /** Este foreach solo debería iterar una vez, puesto que no debería existir dos comprobantes con el mismo id y con las condiciones anteriormente establecidas. */
            foreach ($collection as $comprobante)
            {
                $url = $comprobante->getLink();
                $link = "<a href='".$url."'>Ver PDF</a>";
            }
        }
        return $link;
    }
}