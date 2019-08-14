<?php

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->getConnection()
	->addColumn($installer->getTable('eye4fraud_connector/status'),
		'created_at',
		array(
			'type' => Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
			'nullable' => false,
			'default' => Varien_Db_Ddl_Table::TIMESTAMP_INIT,
			'comment' => 'Created At'
		)
	);

$installer->endSetup();