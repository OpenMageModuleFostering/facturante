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

        $storeId = 0;
        if (strlen($code = Mage::getSingleton('adminhtml/config_data')->getStore())) // store level
        {
            $storeId = Mage::getModel('core/store')->load($code)->getId();
        } elseif (strlen($code = Mage::getSingleton('adminhtml/config_data')->getWebsite())) // website level
        {
            $websiteId = Mage::getModel('core/website')->load($code)->getId();
            $storeId = Mage::app()->getWebsite($websiteId)->getDefaultStore()->getId();
        }

        $usernameValue = Mage::getStoreConfig('facturante/connection/username', $storeId);
        $passwordValue = Mage::getStoreConfig('facturante/connection/password', $storeId);
        $businessIdValue = Mage::getStoreConfig('facturante/connection/business_id', $storeId);

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