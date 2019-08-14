<?php

/**
 * Extend Authorize.net payment instance to keep access to response data

 * @category    Eye4Fraud
 * @package     Eye4Fraud_Connector
 * @author      Mikhail Valiushko
 */
class Eye4Fraud_Connector_Model_Authorizenet extends Mage_Paygate_Model_Authorizenet
{
    /** @var Mage_Paygate_Model_Authorizenet_Result  */
    protected $responseData = null;
	/** @var bool|string Card Number  */
    protected $cardNumber = false;

    /**
     * Post request to gateway and return responce
     *
     * @param Mage_Paygate_Model_Authorizenet_Request|Varien_Object $request
     * @return Mage_Paygate_Model_Authorizenet_Result
     */
    protected function _postRequest(Varien_Object $request)
    {
        $this->responseData = parent::_postRequest($request);
		$this->cardNumber = $request->getData('x_card_num');
        return $this->responseData;
    }

    /**
     * Return response data
     * @return Mage_Paygate_Model_Authorizenet_Result
	 */
    public function getResponseData(){
        return $this->responseData;
    }

	/**
	 * Return first 6 digits of card
	 * @return bool|string
	 */
    public function getCardNumber(){
    	return $this->cardNumber;
	}
}