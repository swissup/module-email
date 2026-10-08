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

    /**
     * Do not select the message body: collections feed the admin grid,
     * the body is loaded by the model only when a single message is viewed.
     *
     * @return $this
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->getSelect()
            ->reset(\Magento\Framework\DB\Select::COLUMNS)
            ->columns(['entity_id', 'from', 'to', 'subject', 'service_id', 'created_at']);

        return $this;
    }
}
