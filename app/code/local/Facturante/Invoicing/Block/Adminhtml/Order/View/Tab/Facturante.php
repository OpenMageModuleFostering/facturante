<?php
/**
 * Admin order tab interface
 *
 * @category    Facturante
 * @package     Invoicing
 */
class Facturante_Invoicing_Block_Adminhtml_Order_View_Tab_Facturante extends Mage_Adminhtml_Block_Template implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate( 'facturante/order/view/tab/facturante.phtml' );
    }

    public function getTabLabel() {
        return $this->__('Facturante');
    }

    public function getTabTitle() {
        return $this->__('Facturante');
    }

    public function canShowTab() {
        return true;
    }

    public function isHidden() {
        return false;
    }

    public function getOrder(){
        return Mage::registry('current_order');
    }
}