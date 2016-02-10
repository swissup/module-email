<?php
namespace Swissup\Email\Model\Service\Source;

use Swissup\Email\Model\Service\CollectionFactory as ServiceCollectionFactory;

class Service implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var ServiceCollectionFactory
     */
    protected $serviceCollectionFactory;

    /**
     * Constructor
     *
     * @param ServiceCollectionFactory $serviceCollectionFactory
     */
    public function __construct(ServiceCollectionFactory $serviceCollectionFactory)
    {
        $this->serviceCollectionFactory = $serviceCollectionFactory;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options[] = ['label' => 'Magento built-in service', 'value' => ''];

        $serviceCollection = $this->serviceCollectionFactory->create()
            ->addStatusFilter()
        ;
        foreach ($serviceCollection as $service) {
            $options[] = [
                'label' => $service->getName(),
                'value' => $service->getId(),
            ];
        }
        return $options;
    }
}
