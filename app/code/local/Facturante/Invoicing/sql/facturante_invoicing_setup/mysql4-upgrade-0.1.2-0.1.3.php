<?php
/**
 * @category  Facturante
 * @package   Facturante_Invoicing
 */

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

Mage::log('Adding order_invoicingstatus table', null,'facturante-module-upgrade-0.1.2.log');

if (!$installer->tableExists($installer->getTable('facturante_invoicing/order_invoicingstatus'))) {
	$installer->run("
		CREATE TABLE `{$installer->getTable('facturante_invoicing/order_invoicingstatus')}` (
		  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `order_increment_id` int(10) unsigned NOT NULL,
		  `order_id` int(10) unsigned NOT NULL,
          `idcomprobante` int(10) unsigned DEFAULT NULL,
		  `comments` varchar(255) DEFAULT NULL,
		  `status` varchar(255) DEFAULT NULL,
		  `link` varchar(255) DEFAULT NULL,
		  `update_date` datetime DEFAULT NULL,
		  PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	");
}

Mage::log('Added order_invoicingstatus table', null,'facturante-module-upgrade-0.1.2.log');

$installer->endSetup();