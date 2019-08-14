<?php
/**
 * Resource model for single request in cache
 *
 * @category   Eye4Fraud
 * @package    Eye4fraud_Connector
 */
class Eye4Fraud_Connector_Model_Resource_Requests_Cache extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Initialize main table and table id field
     */
    protected function _construct()
    {
        $this->_init('eye4fraud_connector/requests_cache', 'request_id');
    }

}