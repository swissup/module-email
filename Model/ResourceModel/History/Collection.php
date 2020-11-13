<?php
namespace Swissup\Email\Model\ResourceModel\History;

/* Swissup/Email/Model/ResourceModel/History/Collection.php */
/**
 * Email History Collection
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Swissup\Email\Model\History::class, \Swissup\Email\Model\ResourceModel\History::class);
    }
}
