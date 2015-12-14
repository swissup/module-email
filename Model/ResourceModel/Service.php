<?php
namespace Swissup\Email\Model\Resource;

/**
 * Email Service mysql resource
 */
class Service extends \Magento\Framework\Model\Resource\Db\AbstractDb
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
