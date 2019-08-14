<?php

/**
 * Show extension version
 * @category    Eye4fraud
 * @package     Eye4fraud_Connector
 */
class Eye4Fraud_Connector_Model_Config_Frontend_Version extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * Render config field
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element){
        $config = Mage::getConfig();
		$module_config = $config->getModuleConfig('Eye4Fraud_Connector');

		$element->setData('value', "[Dummy]");

		$html = parent::render($element);
		$html = str_replace("[Dummy]", '<b>'.(string)$module_config->version."</b>", $html);
        return $html;
    }
}