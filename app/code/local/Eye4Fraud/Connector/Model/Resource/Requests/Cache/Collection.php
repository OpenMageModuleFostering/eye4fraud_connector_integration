<?php

/**
 * Collection of cached requests
 *
 * @category   Eye4Fraud
 * @package    Eye4fraud_Connector
 */
class Eye4Fraud_Connector_Model_Resource_Requests_Cache_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * Resource initialization
     */
    protected function _construct()
    {
        $this->_init('eye4fraud_connector/request','eye4fraud_connector/requests_cache');
    }

    /**
     * @return $this
     */
    protected function _afterLoad(){
        parent::_afterLoad();
        return $this;
    }

    /**
     * Retreive option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return parent::_toOptionArray('id', 'request_id');
    }

    /**
     * Retreive option hash
     *
     * @return array
     */
    public function toOptionHash()
    {
        return parent::_toOptionHash('id', 'request_id');
    }
}
