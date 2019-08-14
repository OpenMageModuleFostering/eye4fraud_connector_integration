<?php

/**
 * Collection of fraud statuses of orders
 *
 * @category   Eye4Fraud
 * @package    Eye4fraud_Connector
 */
class Eye4Fraud_Connector_Model_Resource_Status_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * All requested statuses
     * @var array
     */
    protected $statuses = array();

    /**
     * Collection loaded in cron
     * @var bool
     */
    protected $_cronFlag = false;

	/**
	 * @var Mage_Sales_Model_Resource_Order_Grid_Collection
	 */
    protected $ordersGridCollection;

    /**
     * Resource initialization
     */
    protected function _construct()
    {
        $this->_init('eye4fraud_connector/status');
    }

	/**
	 * Set Orders grid collection
	 * @param Mage_Sales_Model_Resource_Order_Grid_Collection $ordersGridCollection
	 * @return $this
	 */
    public function setOrdersGridCollection($ordersGridCollection){
		$this->ordersGridCollection = $ordersGridCollection;
		$this->statuses = array();
		foreach ($this->ordersGridCollection as $order) $this->statuses[$order['increment_id']] = 0;
		return $this;
	}

    /**
     * Add attribute id to collection
     *
     * @param array $statuses Array of order IDs
     * @return $this
     */
    public function setStatuses($statuses)
    {
        $this->statuses = $statuses;
        $this->addFieldToFilter('order_id', array('in'=>array_keys($statuses)));
        return $this;
    }

	/**
	 * Prepare collection for cron update
	 */
    public function prepareCronUpdateQuery(){
		$helper = Mage::helper('eye4fraud_connector');

		$finalStatuses = $helper->getFinalStatuses();
		$requestInterval = $helper->getConfig("cron_settings/update_interval");
		$requestInterval || $requestInterval = 60;
		$maxDate = Mage::getModel('core/date')->date('Y-m-d H:i:s', time() - $requestInterval*60);

		$update_limit = $helper->getConfig("general/update_limit");
		$update_limit || $update_limit = 5;
		$minDate = Mage::getModel('core/date')->date('Y-m-d H:i:s', time() - $update_limit*60*60*24);

		$update_limit_no_order = $helper->getConfig("general/update_limit_no_order");
		$update_limit_no_order || $update_limit_no_order = 2;
		$minDateNoOrder = Mage::getModel('core/date')->date('Y-m-d H:i:s', time() - $update_limit_no_order*60*60);

		$this->exceptStatuses($finalStatuses)
			->updatedBefore($maxDate)->notOlderThan($minDate, $minDateNoOrder)
			->limitRecordsCount(50)->setCronFlag(true);

		return $this;
	}

    /**
     * Select all statuses except
     * @param $statuses
     * @return $this
     */
    public function exceptStatuses($statuses){
        if(!is_array($statuses)) return $this;
        $this->getSelect()->where('status NOT IN (?)',$statuses);
        return $this;
    }

    /**
     * Limit collection by update date
     * @param $timestamp
     * @return $this
     */
    public function updatedBefore($timestamp){
        $this->getSelect()->where('updated_at < ?',$timestamp);
        return $this;
    }

	public function notOlderThan($update_limit, $update_limit_no_order){
		$this->getSelect()->where('(created_at > "'.$update_limit.'" and status!="N") or (created_at > "'.$update_limit_no_order.'" and status="N") or status="W"');
		return $this;
	}

	public function limitRecordsCount($limit){
        $this->getSelect()->limit($limit);
        return $this;
    }

    /**
     * Set cron flag
     * @param bool $flag
     * @return $this
     */
    public function setCronFlag($flag){
        $this->_cronFlag = $flag;
        return $this;
    }

    /**
     * @return $this
     */
    protected function _afterLoad(){
        parent::_afterLoad();
//        if($this->_cronFlag) {
//			Mage::helper("eye4fraud_connector")->log($this->getSelect()->assemble());
//		}
        $helper = Mage::helper("eye4fraud_connector");
        $isCronEnabled = $helper->getConfig('cron_settings/enabled');

        foreach ($this->_items as $item) {
            /** @var Eye4Fraud_Connector_Model_Status $item */
            if($this->_cronFlag or (!$isCronEnabled and $this->isItemUpdateAllowed($item))){
                $this->statuses[$item->getData('order_id')] = 0;
            }
            else $this->statuses[$item->getData('order_id')] = 1;
        }
        Mage::helper("eye4fraud_connector")->log(json_encode($this->statuses));
        foreach($this->statuses as $orderId=>$loaded){
            if(!$loaded){
                if($item = $this->getItemById($orderId)){
                    $item->retrieveStatus();
                }
                else{
                    /** @var Eye4Fraud_Connector_Model_Status $item */
                    $item = Mage::getModel("eye4fraud_connector/status");
                    $item->isObjectNew(true);
                    $item->setData('order_id', $orderId);
                    $item->retrieveStatus();
                    $this->addItem($item);
                }
            }
        }
        return $this;
    }

	/**
	 * Check if status can be updated by its current status and creation date
	 * @param Eye4Fraud_Connector_Model_Status $item
	 * @return bool
	 */
    protected function isItemUpdateAllowed($item){
		$helper = Mage::helper("eye4fraud_connector");
		$final_statuses = $helper->getFinalStatuses();
		$is_status_final = in_array($item['status'],$final_statuses);

		$update_allowed_by_date = false;
		if($item->getData('status')=='W'){
			$update_allowed_by_date = true;
		}
		elseif($item->getData('status')=='N'){
			$update_limit_no_order = $helper->getConfig("general/update_limit_no_order");
			$update_limit_no_order || $update_limit_no_order = 2;
			$minDateNoOrder = time() - $update_limit_no_order*60*60;
			$created_at = strtotime($item->getData('created_at'));
			if($created_at > $minDateNoOrder) $update_allowed_by_date = true;
		}
		else{
			$update_limit = $helper->getConfig("general/update_limit");
			$update_limit || $update_limit = 5;
			$minDate = time() - $update_limit*60*60*24;
			$created_at = strtotime($item->getData('created_at'));
			if($created_at > $minDate) $update_allowed_by_date = true;
		}

		return (!$is_status_final and $update_allowed_by_date);
	}

    /**
     * Get fraud status from status Model
     * @param Mage_Sales_Model_Order $order
     * @return string
     */
    public function getOrderStatus($order){
        $fraudStatusItem = $this->getItemById($order->getData('increment_id'));
        if($fraudStatusItem==null) return "IER";
        $status = $fraudStatusItem->getData('status');
        return $status;
    }

    public function getOrderStatusLabel($order){
        $status = $this->getOrderStatus($order);
        return Mage::helper('eye4fraud_connector')->__("status:".$status);
    }

	/**
	 * @param $renderedValue
	 * @param Mage_Sales_Model_Order $order
	 * @param $column
	 * @return string
	 */
    public function addStatusDescription($renderedValue, $order, $column){
        $fraudStatusItem = $this->getItemById($order->getData('increment_id'));
        if($fraudStatusItem==null) return $renderedValue;
		/** @var Eye4Fraud_Connector_Helper_Data $helper */
		$helper = Mage::helper('eye4fraud_connector');
        $description = $fraudStatusItem->getData('description');
		$updateMinutes = 0;
		$color = 'inherit';
		$title = '';
		if(in_array($fraudStatusItem->getData('status'), $helper->getFinalStatuses())) $color = 'green';
		if($helper->getConfig('cron_settings/enabled')=="1" and !in_array($fraudStatusItem->getData('status'), $helper->getFinalStatuses())){
			$currentTimestamp = Mage::getModel('core/date')->timestamp(time());
			$updateMinutes = round(($currentTimestamp - strtotime($fraudStatusItem->getData('updated_at')))/60,2);
			$created_interval = round(($currentTimestamp - strtotime($fraudStatusItem->getData('created_at')))/60/60/24, 3);

			$update_interval = floatval($helper->getConfig('cron_settings/update_interval'));
			$update_limit = floatval($helper->getConfig('general/update_limit'));
			if($fraudStatusItem->getData('status')!='N' and $updateMinutes > $update_interval and $created_interval < $update_limit){
				if($updateMinutes<60) $text = $updateMinutes."m";
				else{
					$updateMinutes = round($updateMinutes/60,2);
					$text = $updateMinutes."h";
				}
				$title = "Updated ".$text." ago";
				$color = "red";
			}
		}
		$info_mark = '';
        if($renderedValue!=$description){
			$info_mark = '<img style="vertical-align:middle; margin-top:-3px;" src="'.Mage::getDesign()->getSkinUrl('images/i_question-mark.png').'" title="'.$description.'"/>';
		}
		//$refresh_button = '<img style="vertical-align:middle; margin-top:-3px;" src="'.Mage::getDesign()->getSkinUrl('images/fam_refresh.gif').'" title="Refresh"/>';
		$html = '<div><span style="color: '.$color.'" title="'.$title.'">'.$renderedValue.'</span>'.$info_mark.'</div>';


		//$html .= '<button type="button"><img src="'.Mage::getDesign()->getSkinUrl('images/fam_refresh.gif').'"></button>';
        return $html;
    }

    /**
     * Retreive option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return parent::_toOptionArray('id', 'record_id');
    }

    /**
     * Retreive option hash
     *
     * @return array
     */
    public function toOptionHash()
    {
        return parent::_toOptionHash('id', 'record_id');
    }
}
