<?php

/**
 * Take order model and change event
 * @author Mikhail Valiushka
 * @since 1.2.5
 */
class Eye4Fraud_Connector_Model_Sales_Order extends Mage_Sales_Model_Order {

	/**
	 * Skip order events
	 *
	 * @var string
	 */
	protected $_eventPrefix = 'ef_sales_order';

}