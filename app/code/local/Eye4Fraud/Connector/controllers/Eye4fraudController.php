<?php

/**
 * Eye4fraud admin area controller
 *
 * @category   Eye4Fraud
 * @package    Eye4Fraud_Connector
 * @author     Michael
 */
class Eye4Fraud_Connector_Eye4fraudController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Customer addresses list
     */
    public function indexAction()
    {
        echo "nothingness is there....";
        exit;
    }

    /**
     * Remove saved card
     */
    public function logfileAction(){
		header("Content-type: text/plain");
		header("Content-Disposition: attachment; filename=eye4fraud_debug.log");

		$helper = Mage::helper("eye4fraud_connector");
		if(!$helper->getLogSize()) return;

		$file_path = $helper->getLogFilePath();
		if(!file_exists($file_path)) return;

		$f = fopen($file_path, 'r');
		while (!feof($f)) {
			echo fgets($f);
		}

		fclose($f);
		exit;
    }
}