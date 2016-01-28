<?php
namespace Swissup\Email\Model\ResourceModel;

/**
 * Email Service mysql resource
 */
class Service extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('swissup_email_service', 'id');
    }
}
