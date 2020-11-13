<?php
namespace Swissup\Email\Model\ResourceModel\Service;

use Swissup\Email\Api\Data\ServiceInterface;
use Swissup\Email\Model\Service;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Swissup\Email\Model\Service::class, \Swissup\Email\Model\ResourceModel\Service::class);
    }

    public function addStatusFilter($status = ServiceInterface::ENABLED)
    {
        $this->getSelect()
            ->where('main_table.status = ?', $status)
        ;
        return $this;
    }
}
