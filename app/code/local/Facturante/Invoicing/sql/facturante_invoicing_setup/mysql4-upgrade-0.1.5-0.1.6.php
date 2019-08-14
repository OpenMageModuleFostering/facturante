<?php
/**
 * Module upgrade: Add 'Argentina' provinces
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
    (NULL, 'AR', 'BA', 'Buenos Aires'),
    (NULL, 'AR', 'CA', 'Catamarca'),
    (NULL, 'AR', 'CH', 'Chaco'),
    (NULL, 'AR', 'CU', 'Chubut'),
    (NULL, 'AR', 'CO', 'Córdoba'),
    (NULL, 'AR', 'CR', 'Corrientes'),
    (NULL, 'AR', 'ER', 'Entre Ríos'),
    (NULL, 'AR', 'FO', 'Formosa'),
    (NULL, 'AR', 'JU', 'Jujuy'),
    (NULL, 'AR', 'LP', 'La Pampa'),
    (NULL, 'AR', 'LR', 'La Rioja'),
    (NULL, 'AR', 'MZ', 'Mendoza'),
    (NULL, 'AR', 'MI', 'Misiones'),
    (NULL, 'AR', 'NE', 'Neuquén'),
    (NULL, 'AR', 'RN', 'Río Negro'),
    (NULL, 'AR', 'SA', 'Salta'),
    (NULL, 'AR', 'SJ', 'San Juan'),
    (NULL, 'AR', 'SL', 'San Luis'),
    (NULL, 'AR', 'SC', 'Santa Cruz'),
    (NULL, 'AR', 'SF', 'Santa Fe'),
    (NULL, 'AR', 'SE', 'Santiago del Estero'),
    (NULL, 'AR', 'TF', 'Tierra del Fuego'),
    (NULL, 'AR', 'TU', 'Tucumán');
");

$installer->endSetup();