<?php
namespace Swissup\Email\Model;

use Swissup\Email\Api\Data;
use Swissup\Email\Api\ServiceRepositoryInterface;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Swissup\Email\Model\ResourceModel\Service as ResourceService;
use Swissup\Email\Model\ResourceModel\Service\CollectionFactory as ServiceCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class ServiceRepository
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ServiceRepository implements ServiceRepositoryInterface
{
    /**
     * @var ResourceService
     */
    protected $resource;

    /**
     * @var ServiceFactory
     */
    protected $serviceFactory;

    /**
     * @var ServiceCollectionFactory
     */
    protected $serviceCollectionFactory;

    /**
     * @var Data\ServiceSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var DataObjectProcessor
     */
    protected $dataObjectProcessor;

    /**
     * @var \Swissup\Email\Api\Data\ServiceInterfaceFactory
     */
    protected $dataServiceFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param ResourceService $resource
     * @param ServiceFactory $serviceFactory
     * @param Data\ServiceInterfaceFactory $dataServiceFactory
     * @param ServiceCollectionFactory $serviceCollectionFactory
     * @param Data\ServiceSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourceService $resource,
        ServiceFactory $serviceFactory,
        \Swissup\Email\Api\Data\ServiceInterfaceFactory $dataServiceFactory,
        ServiceCollectionFactory $serviceCollectionFactory,
        Data\ServiceSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager
    ) {
        $this->resource = $resource;
        $this->serviceFactory = $serviceFactory;
        $this->serviceCollectionFactory = $serviceCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataServiceFactory = $dataServiceFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
    }

    /**
     * Save data
     *
     * @param Swissup\Email\Api\Data\ServiceInterface $service
     * @return Service
     * @throws CouldNotSaveException
     */
    public function save(Data\ServiceInterface $service)
    {
        $storeId = $this->storeManager->getStore()->getId();
        $service->setStoreId($storeId);
        try {
            $this->resource->save($service);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $service;
    }

    /**
     * Load data by given Identity
     *
     * @param string $serviceId
     * @return Service
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($serviceId)
    {
        $service = $this->serviceFactory->create();
        $this->resource->load($service, $serviceId);
        if (!$service->getId()) {
            throw new NoSuchEntityException(__('Item with id "%1" does not exist.', $serviceId));
        }
        return $service;
    }

    /**
     * Load data collection by given search criteria
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
     * @return \Swissup\Email\Model\ResourceModel\Service\Collection
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $criteria)
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $collection = $this->serviceCollectionFactory->create();
        foreach ($criteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                if ($filter->getField() === 'store_id') {
                    $collection->addStoreFilter($filter->getValue(), false);
                    continue;
                }
                $condition = $filter->getConditionType() ?: 'eq';
                $collection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
            }
        }
        $searchResults->setTotalCount($collection->getSize());
        $sortOrders = $criteria->getSortOrders();
        if ($sortOrders) {
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
                );
            }
        }
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());
        $services = [];
        /** @var Service $serviceModel */
        foreach ($collection as $serviceModel) {
            $serviceData = $this->dataServiceFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $serviceData,
                $serviceModel->getData(),
                'Swissup\Email\Api\Data\ServiceInterface'
            );
            $services[] = $this->dataObjectProcessor->buildOutputDataArray(
                $serviceData,
                'Swissup\Email\Api\Data\ServiceInterface'
            );
        }
        $searchResults->setItems($services);
        return $searchResults;
    }

    /**
     * Delete Service
     *
     * @param \Swissup\Email\Api\Data\ServiceInterface $service
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(Data\ServiceInterface $service)
    {
        try {
            $this->resource->delete($service);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * Delete by given Identity
     *
     * @param string $serviceId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById($serviceId)
    {
        return $this->delete($this->getById($serviceId));
    }
}
