<?php
/**
 * Module upgrade: Add order 'afip_last_invoice_type' attribute
 *
 * @category  Facturante
 * @package   Facturante_Invoicing
 */

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

Mage::log('Adding attribute', null,'facturante-module-upgrade-0.1.5.log');

$this->addAttribute('order', 'afip_last_invoice_type', array(
	'type'          => 'varchar',
	'label'         => 'Ultimo Tipo de Factura',
	'visible'       => false,
	'required'      => false,
	'visible_on_front' => false,
	'user_defined'  =>  false,
	'default'		=> ''
));

Mage::log('Added attribute', null,'facturante-module-upgrade-0.1.5.log');

$installer->endSetup();