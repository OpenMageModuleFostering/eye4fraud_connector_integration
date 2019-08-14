<?php
/**
 * Model of single fraud status for one order
 *
 * @category   Eye4Fraud
 * @package    Eye4fraud_Connector
 */
class Eye4Fraud_Connector_Model_Status extends Mage_Core_Model_Abstract
{
    protected $_eventPrefix = 'eye4fraud_connector_status';

    protected function _construct(){
        $this->_init('eye4fraud_connector/status');
    }

    /**
     * Retrieve status from remote server and save model
     * @return $this
     */
    public function retrieveStatus(){
        $fraudData = Mage::helper('eye4fraud_connector')->getOrderStatus($this->getData('order_id'));
        if(empty($fraudData)) {
            $this->setData('status', 'RER');
            $this->setData('description', 'Connection Error');
            $this->setData('updated_at', Mage::getModel('core/date')->date('Y-m-d H:i:s'));
            return $this;
        }
        if(isset($fraudData['error']) and $fraudData['error']){
            $this->setData('error', true);
        }
        $status = $fraudData['StatusCode'];
		if($fraudData['StatusCode']=='E' and strpos($fraudData['Description'], 'No Order')!==false){
			$status = 'N';
		}
        $this->setData('status', $status);
        $this->setData('description', $fraudData['Description']);
        $this->setData('updated_at', Mage::getModel('core/date')->date('Y-m-d H:i:s'));
        if(!$this->getOrigData('created_at')) $this->setData('created_at', Mage::getModel('core/date')->date('Y-m-d H:i:s'));
        /**
         * A little hack to restore order_id field after model was saved
         */
        $tmp_order_id = $this->getData('order_id');
        $this->save();
        $this->setData('order_id',$tmp_order_id);
        return $this;
    }

	/**
	 * Create queued status after request was cached
	 * @param string $order_id
	 * @return $this
	 */
    public function createQueued($order_id){
		$this->setData('order_id', $order_id);
		$this->setData('status', 'Q');
		$this->setData('description', 'Request Queued');
		$this->setData('updated_at', Mage::getModel('core/date')->date('Y-m-d H:i:s'));
		$this->setData('created_at', Mage::getModel('core/date')->date('Y-m-d H:i:s'));
		$this->isObjectNew(true);
		return $this;
	}

	/**
	 * Changes status to Awaiting Response and allow to save
	 */
	public function setWaitingStatus(){
    	$this->setData('status', 'W');
		$this->setData('description', 'Waiting Update');
		return $this;
	}

    /**
     * Set or get flag is object new
     * @param null $flag
     * @return bool
     */
    public function isObjectNew($flag=null){
        $this->getResource()->setNewFlag(true);
        return parent::isObjectNew($flag);
    }

}
