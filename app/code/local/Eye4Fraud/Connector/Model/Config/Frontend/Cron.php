<?php

/**
 * Extension cron status
 * @category    Eye4fraud
 * @package     Eye4fraud_Connector
 */
class Eye4Fraud_Connector_Model_Config_Frontend_Cron extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Render config field
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element){

		$cron_task_finished = $this->getCronFinishedTask();
		$cron_task_pending = $this->getCronPendingTask();

		$helper = Mage::helper('eye4fraud_connector');
		$requestInterval = intval($helper->getConfig("cron_settings/update_interval"));

		$value = '';
		if(!$cron_task_finished->isEmpty()){
			$color = 'green';
			$tooltip = '';
			if($cron_task_finished->getData('finished_at')){
				$finished_interval = time() - strtotime($cron_task_finished->getData('finished_at'));
				if($finished_interval > ($requestInterval*60+3)) {
					$color = 'red';
					$tooltip = 'Task finished more than '.$requestInterval.'m ago';
				}
			}
			$value .= '<div>Last cron task finished at <span style="color: '.$color.'" title="'.$tooltip.'">'.$cron_task_finished->getData('finished_at').'</span></div>';
		}
		else{
			$value .= '<div>Finished cron task not found</div>';
		}

		if(!$cron_task_pending->isEmpty()){
			$color = 'green';
			$tooltip = '';
			$pending_date_time = 'Not set yet';
			if($cron_task_pending->getData('scheduled_at')){
				$pending_interval = strtotime($cron_task_pending->getData('scheduled_at')) - time();
				$pending_date_time = Mage::getModel('core/date')->date(null, $cron_task_pending->getData('scheduled_at'));
				if($pending_interval < 0) {
					$color = 'red';
					$tooltip = 'Task scheduled time is in the past';
				}
			}
			$value .= '<div>Cron task scheduled at <span style="color: '.$color.'" title="'.$tooltip.'">'.$pending_date_time.'</span></div>';
		}
		else{
			$value .= '<div>Scheduled cron task <strong>NOT</strong> found</div>';
		}

		$value .= '<input type="hidden" value="0" id="eye4fraud_connector_cron_settings_cron_task">';

		$element->setData('value', '[dummy]');
		$html = parent::render($element);
		$html = str_replace('[dummy]',$value, $html);
        return $html;
    }

	/**
	 * Get Last finished cron task
	 * @return Mage_Cron_Model_Schedule|Varien_Object
	 */
    protected function getCronFinishedTask(){
		/** @var Mage_Cron_Model_Resource_Schedule_Collection $collection */
		$collection = Mage::getModel('cron/schedule')->getCollection();
		$collection->addFieldToFilter('job_code', 'eye4fraud_refresh');
		$collection->addFieldToFilter('status', Mage_Cron_Model_Schedule::STATUS_SUCCESS);
		$collection->setOrder('finished_at', Mage_Cron_Model_Resource_Schedule_Collection::SORT_ORDER_DESC);
		return $collection->getFirstItem();
	}

	/**
	 * Get first pending cron task
	 * @return Mage_Cron_Model_Schedule|Varien_Object
	 */
	protected function getCronPendingTask(){
		/** @var Mage_Cron_Model_Resource_Schedule_Collection $collection */
		$collection = Mage::getModel('cron/schedule')->getCollection();
		$collection->addFieldToFilter('job_code', 'eye4fraud_refresh');
		$collection->addFieldToFilter('status', Mage_Cron_Model_Schedule::STATUS_PENDING);
		$collection->setOrder('scheduled_at', Mage_Cron_Model_Resource_Schedule_Collection::SORT_ORDER_ASC);
		return $collection->getFirstItem();
	}

}