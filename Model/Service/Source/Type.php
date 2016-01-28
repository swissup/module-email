<?php
namespace Swissup\Email\Model\Service\Source;

class Type implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Swissup\Email\Model\Service
     */
    protected $service;

    /**
     * Constructor
     *
     * @param \Swissup\Email\Model\Service $service
     */
    public function __construct(\Swissup\Email\Model\Service $service)
    {
        $this->service = $service;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options[] = ['label' => '', 'value' => ''];
        $availableOptions = $this->service->getTypes();
        foreach ($availableOptions as $key => $value) {
            $options[] = [
                'label' => $value,
                'value' => $key,
            ];
        }
        return $options;
    }
}
