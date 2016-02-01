<?php
namespace Swissup\Email\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface for email services search results.
 * @api
 */
interface ServiceSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get list.
     *
     * @return \Swissup\Email\Api\Data\ServiceInterface[]
     */
    public function getItems();

    /**
     * Set list.
     *
     * @param \Swissup\Email\Api\Data\ServiceInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
