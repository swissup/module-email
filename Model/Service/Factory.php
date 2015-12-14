<?php
namespace Swissup\Email\Model\Service;

class Factory
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     *
     * @return \Swissup\Email\Model\Service
     */
    public function create()
    {
        return $this->_objectManager->create('Swissup\Email\Model\Service');
    }
}
