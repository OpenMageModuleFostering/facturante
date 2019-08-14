<?php
/**
 * Facturante module system config form field
 *
 * @category    Facturante
 * @package     Invoicing
 */
class Facturante_Invoicing_Block_System_Config_Form_Fieldset_Connection_Test extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * Add jQuery to system configuration section
     */
    public function _prepareLayout()
    {
        $head = $this->getLayout()->getBlock('head');
        $head->addJs('lib/jquery/jquery-1.10.2.js');
        $head->addJs('lib/jquery/noconflict.js');

        return parent::_prepareLayout();
    }

    /**
     * Customize test connection system configuration element
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $block = Mage::app()->getLayout()->createBlock('adminhtml/widget_form_renderer_element')
            ->setTemplate('facturante/form/testconnection.phtml');

        $usernameValue = Mage::getStoreConfig('facturante/connection/username');
        $passwordValue = Mage::getStoreConfig('facturante/connection/password');
        $businessIdValue = Mage::getStoreConfig('facturante/connection/business_id');

        if($usernameValue != '' && $passwordValue != '' && $businessIdValue != '')
        {
            if (Mage::helper('facturante_invoicing')->testAPIConnection())
            {
                $block->setSuccessfulApiConnection(true);
            }
        }

        return $block->toHtml();
    }


}