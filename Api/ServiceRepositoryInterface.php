<?php
namespace Swissup\Email\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * CRUD interface.
 * @api
 */
interface ServiceRepositoryInterface
{
    /**
     * Save
     *
     * @param \Swissup\Email\Api\Data\ServiceInterface $service
     * @return \Swissup\Email\Api\Data\ServiceInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(Data\ServiceInterface $service);

    /**
     * Retrieve
     *
     * @param int $serviceId
     * @return \Swissup\Email\Api\Data\ServiceInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($serviceId);

    /**
     * Retrieve matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Swissup\Email\Model\ResourceModel\Service\Collection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Delete
     *
     * @param \Swissup\Email\Api\Data\ServiceInterface $service
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(Data\ServiceInterface $service);

    /**
     * Delete by ID.
     *
     * @param int $serviceId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($serviceId);
}
