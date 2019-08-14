<?php
/**
 * Module upgrade: Update structure of 'Argentina' provinces to ISO format.
 *
 * @category  Facturante
 * @package   Facturante_Invoicing
 */

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$setup = $this;

$setup->run("
   INSERT INTO `directory_country_region` (`region_id`, `country_id`, `code`, `default_name`) VALUES
    (NULL, 'AR', '0', 'Ciudad Autónoma de Buenos Aires');
    ");
$setup->run("
   UPDATE `directory_country_region` SET code = 1 WHERE default_name = 'Buenos Aires';
   UPDATE `directory_country_region` SET code = 2 WHERE default_name = 'Catamarca';
   UPDATE `directory_country_region` SET code = 3 WHERE default_name = 'Córdoba';
   UPDATE `directory_country_region` SET code = 4 WHERE default_name = 'Corrientes';
   UPDATE `directory_country_region` SET code = 5 WHERE default_name = 'Entre Ríos';
   UPDATE `directory_country_region` SET code = 6 WHERE default_name = 'Jujuy';
   UPDATE `directory_country_region` SET code = 7 WHERE default_name = 'Mendoza';
   UPDATE `directory_country_region` SET code = 8 WHERE default_name = 'La Rioja';
   UPDATE `directory_country_region` SET code = 9 WHERE default_name = 'Salta';
   UPDATE `directory_country_region` SET code = 10 WHERE default_name = 'San Juan';
   UPDATE `directory_country_region` SET code = 11 WHERE default_name = 'San Luis';
   UPDATE `directory_country_region` SET code = 12 WHERE default_name = 'Santa Fe';
   UPDATE `directory_country_region` SET code = 13 WHERE default_name = 'Santiago del Estero';
   UPDATE `directory_country_region` SET code = 14 WHERE default_name = 'Tucumán';
   UPDATE `directory_country_region` SET code = 16 WHERE default_name = 'Chaco';
   UPDATE `directory_country_region` SET code = 17 WHERE default_name = 'Chubut';
   UPDATE `directory_country_region` SET code = 18 WHERE default_name = 'Formosa';
   UPDATE `directory_country_region` SET code = 19 WHERE default_name = 'Misiones';
   UPDATE `directory_country_region` SET code = 20 WHERE default_name = 'Neuquén';
   UPDATE `directory_country_region` SET code = 21 WHERE default_name = 'La Pampa';
   UPDATE `directory_country_region` SET code = 22 WHERE default_name = 'Río Negro';
   UPDATE `directory_country_region` SET code = 23 WHERE default_name = 'Santa Cruz';
   UPDATE `directory_country_region` SET code = 24 WHERE default_name = 'Tierra del Fuego';
");

Mage::log('Adding attribute to products', null, 'facturante-module-upgrade-0.1.7.log');

$this->addAttribute('catalog_product', 'gtin', array(
    'type'          => 'varchar',
    'size'          => 13,
    'unsigned'      => true,
    'label'         => 'GTIN',
    'visible'       => true,
    'required'      => false,
    'visible_on_front' => false,
    'user_defined'  =>  false,
    'apply_to' => 'simple'
));

Mage::log('Added attribute to products', null, 'facturante-module-upgrade-0.1.7.log');

$installer->endSetup();