<?php

/* @var $installer Mage_Sales_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$installer->addAttribute("order", "eye4fraud_status", array("type"=>"varchar", "grid" => true, 'comment' => 'Eye4Fraud Status'));

$installer->endSetup();