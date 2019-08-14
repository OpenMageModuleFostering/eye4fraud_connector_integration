<?php
/**
 * Eye4fraud Connector Magento Module
 *
 * @category    Eye4fraud
 * @package     Eye4fraud_Connector
 */

class UsaePay {
	private $db;
	private $cfg;
	private $pin;
	private $error;
	private $token;
	private $client;
	private $sourceKey;
	private $order_id = 0;
	private $wsdl = "https://sandbox.usaepay.com/soap/gate/0AE595C1/usaepay.wsdl";	//for live server use "www" for test server use "sandbox"

	public function __construct($sourceKey, $pin, $wsdl = NULL)
	{
		$this->pin		 = $pin;
		$this->sourceKey = $sourceKey;
			
		if(is_null($wsdl)){
			$wsdl = $this->wsdl;
		}
			
		$this->setWsdl($wsdl);
		$this->generateToken($sourceKey, $pin);
	}
	
	private function generateToken($sourceKey, $pin)
	{
		// generate random seed value
		$seed  = mktime() .rand();
		// make hash value using sha1 function
		$clear = $sourceKey .$seed .$pin;
		$hash  = sha1($clear);
		 
		// assembly ueSecurityToken as an array
		$token = array("SourceKey"	=> $sourceKey,
					   "PinHash"	=> array("Type"		 => "sha1",
											 "Seed"		 => $seed,
											 "HashValue" => $hash),
					   "ClientIP"	=> $_SERVER["REMOTE_ADDR"]);
		
		$this->token = $token;
	}
	
	public function setSourceKey($key)
	{
		$this->sourceKey = $key;
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
			$this->client = new SoapClient($wsdl, array("trace" => 1));
		} catch(exception $e) {
			throw new Exception($e->getMessage());
		}
	}

	public function getTransactionDetails($transID)
	{
		// Call function
		try {
			$response = $this->client->getTransaction($this->token, $transID);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
		
		if($response->Response->ResultCode == "E") {
			throw new Exception($response->Response->Result);
		}
		
		return $response->Response;
	}
}
