<?php

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$table = $installer->getConnection()->changeColumn(
	$installer->getTable('eye4fraud_connector/requests_cache'),
	'request_id',
	'request_id',
	array(
		'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
		'identity'  => true,
		'unsigned'  => true,
		'nullable'  => false,
		'primary'   => true,
		'comment'   => 'Request ID'
	)
);