<?php
$installer = $this;

$installer->startSetup();

$setup = new Mage_Eav_Model_Entity_Setup('core_setup');

$entityTypeId     = $setup->getEntityTypeId('customer');
$attributeSetId   = $setup->getDefaultAttributeSetId($entityTypeId);
$attributeGroupId = $setup->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);

/** Agrego el atributo dni_number para customer */
$installer->addAttribute("customer", "dni_number",  array(
    "type"     => "varchar",
    "backend"  => "",
    "label"    => "Número de DNI / CUIT",
    "input"    => "text",
    "source"   => "",
    "visible"  => true,
    "required" => false,
    "default" => "",
    "frontend" => "",
    "unique"     => false
));

$attribute   = Mage::getSingleton("eav/config")->getAttribute("customer", "dni_number");

$setup->addAttributeToGroup(
    $entityTypeId,
    $attributeSetId,
    $attributeGroupId,
    'dni_number',
    '999'  //sort_order
);

$used_in_forms=array();

$used_in_forms[]="adminhtml_customer";
$used_in_forms[]="checkout_register";
$used_in_forms[]="customer_account_create";
$used_in_forms[]="adminhtml_checkout";
$used_in_forms[]="customer_account_edit";
$attribute->setData("used_in_forms", $used_in_forms)
    ->setData("is_used_for_customer_segment", true)
    ->setData("is_system", 0)
    ->setData("is_user_defined", 1)
    ->setData("is_visible", 1)
    ->setData("sort_order", 100)
;
$attribute->save();

/** Agrego el atributo dni_number_address para customer_address */
$installer->addAttribute('customer_address', 'dni_number_address', array(
    'type' => 'varchar',
    'input' => 'text',
    'label' => 'Número de DNI / CUIT',
    'global' => 1,
    'visible' => 1,
    'required' => 0,
    'user_defined' => 1,
    'visible_on_front' => 1
));

Mage::getSingleton('eav/config')
    ->getAttribute('customer_address', 'dni_number_address')
    ->setData('used_in_forms',
        array(
            'customer_register_address',
            'adminhtml_checkout',
            'adminhtml_customer',
            'checkout_register',
            'customer_account_create',
            'customer_account_edit',
            'customer_address_edit',
            'adminhtml_customer_address'
        ))->save();

/**
 * agrego campo a sales_flat_quote_address
 */
$sales_quote_address = $installer->getTable('sales/quote_address');
$installer->getConnection()
    ->addColumn($sales_quote_address, 'dni_number_address', array(
        'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
        'comment' => 'DNI/CUIT'
    ));

/**
 * Agrego campo a sales_flat_order_address
 */
$sales_order_address = $installer->getTable('sales/order_address');
$installer->getConnection()
    ->addColumn($sales_order_address, 'dni_number_address', array(
        'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
        'comment' => 'DNI/CUIT'
    ));

$tablequote = $this->getTable('sales/quote');
$installer->run("ALTER TABLE  $tablequote ADD  `dni_number_address` VARCHAR( 255 ) NOT NULL
");

$installer->endSetup();