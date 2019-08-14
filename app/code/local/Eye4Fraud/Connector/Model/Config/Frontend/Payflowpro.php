<?php

/**
 * Check order grid rewrite
 * @category    Eye4fraud
 * @package     Eye4fraud_Connector
 */
class Eye4Fraud_Connector_Model_Config_Frontend_Payflowpro extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Render config field
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element){
        $model = Mage::helper('payment')->getMethodInstance('verisign');
		$rewrite_status = is_a($model, 'Eye4Fraud_Connector_Model_Payflowpro');
		$element->setData('value', '[dummy]');

		if($rewrite_status){
			$value = '<img src="'.$this->getSkinUrl('images/fam_bullet_success.gif').'" style="margin-bottom: -5px;">';
		}
		else{
			$value = '<img src="'.$this->getSkinUrl('images/error_msg_icon.gif').'" style="margin-bottom: -5px;">';
			$element->setData('comment', get_class($model));
		}
		$html = parent::render($element);
		$html = str_replace('[dummy]', $value, $html);
        return $html;
    }

}