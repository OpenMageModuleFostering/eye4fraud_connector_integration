<?php

/**
 * Insert fraud_status column and render it
 * @author Mikhail Valiushka
 * @since 1.2.5
 */
class Eye4Fraud_Connector_Helper_Order_Grid {

	/**
	 * @param $renderedValue
	 * @param Mage_Sales_Model_Order $order
	 * @param $column
	 * @return string
	 */
	public static function addStatusDescription($renderedValue, $order, $column){
		$status_collection = Mage::getResourceSingleton('eye4fraud_connector/status_collection');
		return $status_collection->addStatusDescription($renderedValue, $order, $column);
	}

	/**
	 * Generate Fraud Statuc column definition for orders grid.
	 * @return array
	 * @see layout/eye4fraud/salesgrid.xml
	 */
	public function generateFraudColumnDefinition(){
		return array(
			'header' => 'Fraud Status',
			'width' => '123',
			'type' => 'text',
			'index' => 'eye4fraud_status',
			'frame_callback' => array('Eye4Fraud_Connector_Helper_Order_Grid','addStatusDescription')
		);
	}
}