<?php
$installer = $this;

$installer->startSetup();

$requiredCountries = Mage::getStoreConfig('general/region/state_required');
Mage::getConfig()->saveConfig('general/region/state_required', $requiredCountries . ',AR');

$installer->endSetup();