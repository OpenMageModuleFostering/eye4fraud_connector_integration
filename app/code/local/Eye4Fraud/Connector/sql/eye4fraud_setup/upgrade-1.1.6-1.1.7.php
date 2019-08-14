<?php

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

/**
 * Create 'Eye4Fraud_Connector/fraud_status' table
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('eye4fraud_connector/requests_cache'))
    ->addColumn('request_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ), 'Request ID')
    ->addColumn('request_data', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
    ), 'Request Data')
    ->addColumn('increment_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 32, array(
        'nullable'  => false,
    ), 'Request Data')
    ->addColumn('payment_method', Varien_Db_Ddl_Table::TYPE_VARCHAR, 100, array(
        'nullable'  => true,
    ), 'Request Data')
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'default'   => Varien_Db_Ddl_Table::TIMESTAMP_INIT,
        'nullable'  => false,
    ), 'Request Data')
    ->addColumn('sent_time', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'nullable'  => true
    ), 'Last sent time')
    ->addColumn('attempts', Varien_Db_Ddl_Table::TYPE_TINYINT, 1, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => 0
    ), 'Current attempts')
    ->addIndex(
        $installer->getIdxName('eye4fraud_connector/requests_cache', array('sent_time')),
        array('sent_time')
    )
    ->addIndex(
        $installer->getIdxName('eye4fraud_connector/requests_cache', array('attempts')),
        array('attempts')
    )
    ->setComment('Requests cache');

$installer->getConnection()->createTable($table);