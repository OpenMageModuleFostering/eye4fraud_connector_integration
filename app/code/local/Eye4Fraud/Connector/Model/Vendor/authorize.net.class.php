<?php
/**
 * Eye4fraud Connector Magento Module
 *
 * @category    Eye4fraud
 * @package     Eye4fraud_Connector
 */

class AuthorizeDotNet
{
	private $wsdl            = 'https://api.authorize.net/soap/v1/Service.asmx?WSDL';
	private $authorize_net_login    = '';
	private $authorize_net_key = '';
	private $soapclient      = null;
	
	private $error = '';
	private $order_id = 0;

	protected $_helper = null;

	public function __construct($authorize_net_login, $authorize_net_key, $wsdl='')
	{
        $this->setAuthorizeNetLogin($authorize_net_login);
        $this->setAuthorizeNetKey($authorize_net_key);
		if(!$wsdl){
			$wsdl = $this->wsdl;
		}
		$this->setWsdl($wsdl);
	}

	public function setAuthorizeNetLogin($id)
	{
		$this->authorize_net_login = $id;
	}

	public function setAuthorizeNetKey($key)
	{
		$this->authorize_net_key = $key;
	}

	public function setError($error)
	{
		$this->error = $error;
	}
	
	public function getError()
	{
		return $this->error;
	}

	public function setWsdl($wsdl)
	{
		try{
			$this->soapclient = new SoapClient($wsdl, array('trace' => 1));
		}
		catch (exception $e)
		{
			throw new Exception($e->getMessage());
		}
	}

	protected function prepareParams()
	{
		// Auth
		$auth = new stdClass;
		$auth->name = $this->authorize_net_login;
		$auth->transactionKey = $this->authorize_net_key;

		// Parameters
		$params = new stdClass;
		$params->merchantAuthentication = $auth;
		
		return $params;
	}	

	public function getTransactionDetails($transId)
	{
		// Prepare params
		$params = $this->prepareParams();
		$params->transId = $transId;
		
		// Call function
		try {
			$response = $this->soapclient->GetTransactionDetails($params);
			$response = $this->_getHelper()->makeCleanArray($response->GetTransactionDetailsResult);
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}
		
		if($response['resultCode'] == 'Error')
		{
			throw new Exception($response['messages']['MessagesTypeMessage']['text']);
		}
		
		return $response['transaction'];
	}

	/**
     * Returns the module helper. Initializes one if not already set.
     * 
     * @return Eye4fraud_Connector_Helper_Data $this->_helper
     */
    protected function _getHelper(){
        if (empty($this->_helper)){
            $this->_helper = Mage::helper("eye4fraud_connector");
        }
        return $this->_helper;
    }
}
