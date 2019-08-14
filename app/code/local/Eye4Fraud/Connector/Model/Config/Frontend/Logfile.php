<?php

/**
 * System setting field rewrite
 * @category    Eye4fraud
 * @package     Eye4fraud_Connector
 */
class Eye4Fraud_Connector_Model_Config_Frontend_Logfile extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Render config field
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element){
		$helper = Mage::helper('eye4fraud_connector');
		$logfile_size = $helper->getLogSize();
		if(!$logfile_size) return $this->__('Log file not exists or empty');
		$element->setData('value', '[dummy]');
		$html = parent::render($element);
		$value = '<a href="'.$this->getUrl('*/eye4fraud/logfile').'" target="_blank">Download</a>&nbsp;<input type="hidden" value="0" id="eye4fraud_connector_general_debug_file"><span>Log file size: '.$logfile_size.'</span>';
		$html = str_replace('[dummy]',$value, $html);
        return $html;
    }

}