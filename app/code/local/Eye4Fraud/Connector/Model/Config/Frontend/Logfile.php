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
		$value = '<a href="'.$this->getUrl('*/eye4fraud/logfile').'" target="_blank">Download Log File</a>&nbsp;<input type="hidden" value="0" id="eye4fraud_connector_general_debug_file"><span>Log file size: '.$logfile_size.'</span>';

		if($helper->getConfig('general/debug_file_rotate')=='1'){
			$value .= $this->generateLogFiles();
		}

		$html = str_replace('[dummy]',$value, $html);
        return $html;
    }

	/**
	 * Generate links to compressed old log files
	 */
    protected function generateLogFiles(){
		$helper = Mage::helper('eye4fraud_connector');
		$links = array('');
		$log_files_count = intval($helper->getConfig('general/debug_file_count'));
		$i = 1;
		while(file_exists($helper->getLogFilePath().$i.'.gz') and ($log_files_count==0 or $i<=$log_files_count)){
			$logfile_size = $helper->fileSizeConvert(filesize($helper->getLogFilePath().$i.'.gz'));
			$date = Mage::getModel('core/date')->date('Y-m-d H:i:s', filemtime($helper->getLogFilePath().$i.'.gz'));
			$links[] = '<a href="'.$this->getUrl('*/eye4fraud/logfile/idx/'.$i).'" target="_blank">Old Log #'.$i.'</a><br><span>File size: '.$logfile_size.' Date: '.$date.'</span>';
			$i ++;
		}
		return implode('<br>',$links);
	}

}