<?php

/**
 * Collection.php.
 * @author Mikhail Valiushka
 * @since 1.2.5
 */
class Eye4Fraud_Connector_Model_Resource_Order_Collection extends Mage_Sales_Model_Resource_Order_Collection{

	/**
	 * Event prefix
	 * @var string
	 */
	protected $_eventPrefix    = 'eye4fraud_order_collection';

	public function addFraudStatusFilter($order_increments){
		$this->addFieldToSelect('entity_id');
		$this->addFieldToSelect('increment_id');
		$this->addFieldToSelect('store_id');
		$this->addFieldToSelect('eye4fraud_status');
		$this->addFieldToFilter('increment_id', array('in' => $order_increments));
	}

}