<?php
/**
 * Eye4fraud Connector Magento Module
 *
 * @category    Eye4fraud
 * @package     Eye4fraud_Connector
 */

class PayFlow
{        
	protected $partner  = '';
	protected $vendor   = '';
	protected $username = '';
	protected $password = '';
	
	private static $instance = 0;
                                    
                                    



	public static function instance()
	{
		if(!self::$instance)
		{
			self::$instance = new PayFlow();
		}
		return self::$instance;
	}

	public function __construct()
	{        
	

	}

	public function setPartner($partner)
	{
		$this->partner = $partner;
		return $this;
	}

	public function setUsername($username)
	{
		$this->username = $username;
		return $this;
	}

	public function setPassword($password)
	{
		$this->password = $password;
		return $this;
	}

	public function setVendor($vendor)
	{
		$this->vendor = $vendor;
		return $this;
	}
	
	public function getTransactionInfo($transaction_id)
	{
		$url = 'https://payflowpro.paypal.com'; // live
		$data = array(
			'TRXTYPE'   => 'I',
			'TENDER'    => 'C',
			'VERBOSITY' => 'HIGH',
			'PARTNER'   => $this->partner,
			'VENDOR'    => $this->vendor,
			'USER'      => $this->username,
			'PWD'       => $this->password,
			'ORIGID'    => $transaction_id,
		);    
		
		


		$curl = Mage::helper("eye4fraud_connector/curl");
		$result = $curl->post($url, $data);   
    
             
		parse_str($result, $key_value_array);
                  
		return array(
			'AVSCode'     => isset($key_value_array['PROCAVS'])  ? $key_value_array['PROCAVS']  : '',
			'CIDResponse' => isset($key_value_array['PROCCVV2']) ? $key_value_array['PROCCVV2'] : '',
		);                                                
	}
}
