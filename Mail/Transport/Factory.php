<?php
namespace Swissup\Email\Mail\Transport;

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
     * @return \Swissup\Email\Mail\Transport
     */
    public function create($type = 'Smtp', $arguments = [])
    {
        $class = "\Swissup\Email\Mail\Transport\\$type";
        return $this->objectManager->create($class, $arguments);
    }
}
