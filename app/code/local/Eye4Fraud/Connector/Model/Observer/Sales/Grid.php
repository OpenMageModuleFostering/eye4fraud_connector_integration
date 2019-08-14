<?php

/**
 * Created by PhpStorm.
 * User: Misha
 * Date: 29.01.2017
 * Time: 16:55
 */
class Eye4Fraud_Connector_Model_Observer_Sales_Grid {

	public function addColumnToResource(Varien_Event_Observer $observer) {
		// Only needed if you use a table other than sales/order (sales_flat_order)
		/** @var Mage_Sales_Model_Resource_Order $resource */
		/*
		$resource = $observer->getEvent()->getResource();
		$resource->addVirtualGridColumn(
			'customer_city',
			'sales/order_address',
			array('billing_address_id' => 'entity_id'),
			'city'
		);
		**/
	}}