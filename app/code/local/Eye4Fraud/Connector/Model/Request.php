<?php
/**
 * Model of single request in cache
 *
 * @category   Eye4Fraud
 * @package    Eye4fraud_Connector
 */
class Eye4Fraud_Connector_Model_Request extends Mage_Core_Model_Abstract
{
    protected $_eventPrefix = 'eye4fraud_connector_requests';

    protected function _construct()
    {
        $this->_init('eye4fraud_connector/requests_cache');
    }

}
