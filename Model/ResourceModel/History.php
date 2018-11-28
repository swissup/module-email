<?php
namespace Swissup\Email\Model\ResourceModel;

/* Swissup/Email/Model/ResourceModel/History.php */
/**
 * Email History mysql resource
 */
class History extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('swissup_email_history', 'entity_id');
    }

}