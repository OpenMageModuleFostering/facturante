<?php
/**
 * Module upgrade: Add quote and order 'afip_invoicing_status' attribute
 *
 * @category  Facturante
 * @package   Facturante_Invoicing
 */

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

Mage::log('Adding attribute', null,'facturante-module-upgrade-0.1.1.log');

$this->addAttribute('order', 'facturante_invoice_status', array(
	'type'          => 'varchar',
	'label'         => 'Estado Facturación',
	'visible'       => true,
	'required'      => false,
	'visible_on_front' => false,
	'user_defined'  =>  false,
	'default'		=> 'Pendiente'
));

Mage::log('Added attribute', null,'facturante-module-upgrade-0.1.1.log');

$installer->endSetup();