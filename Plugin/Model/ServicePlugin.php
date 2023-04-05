<?php
declare(strict_types=1);

namespace Swissup\Email\Plugin\Model;

use Magento\Framework\DataObject;
use Swissup\Email\Model\Service;

class ServicePlugin
{
    /**
     * @var \Swissup\Email\Api\ServiceEncryptorInterface
     */
    private $serviceEncryptor;

    /**
     * @param \Swissup\Email\Api\ServiceEncryptorInterface $serviceEncryptor
     */
    public function __construct(\Swissup\Email\Api\ServiceEncryptorInterface $serviceEncryptor)
    {
        $this->serviceEncryptor = $serviceEncryptor;
    }

    public function afterBeforeSave(Service $subject): void
    {
        $this->serviceEncryptor->encrypt($subject);
    }

    public function afterAfterSave(Service $subject): void
    {
        $this->serviceEncryptor->decrypt($subject);
    }

    public function afterAfterLoad(Service $subject): void
    {
        $this->serviceEncryptor->decrypt($subject);
    }
}