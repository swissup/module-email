<?php
namespace Swissup\Email\Model\Service;

use Swissup\Email\Api\EncryptorInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Swissup\Email\Api\ServiceEncryptorInterface;
use Swissup\Email\Api\Data\ServiceInterface;

class Encryptor implements ServiceEncryptorInterface
{
    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @param EncryptorInterface $encryptor
     */
    public function __construct(EncryptorInterface $encryptor)
    {
        $this->encryptor = $encryptor;
    }

    /**
     * @return string[]
     */
    private function getAttributes()
    {
        return ['password'];
    }

    /**
     * @param DataObject $object
     * @return void
     */
    public function encrypt(ServiceInterface $object): void
    {
        foreach ($this->getAttributes() as $attributeCode) {
            $value = $object->getData($attributeCode);
            if ($value) {
                $object->setData($attributeCode, $this->encryptor->encrypt($value));
            }
        }
    }

    /**
     * @param DataObject $object
     * @return void
     */
    public function decrypt(ServiceInterface $object): void
    {
        foreach ($this->getAttributes() as $attributeCode) {
            $value = $object->getData($attributeCode);
            if ($value) {
                try {
                    $value = $this->encryptor->decrypt($value);
                    $object->setData($attributeCode, $value);
                } catch (\Exception $e) {
                    // value is not encrypted or something wrong with encrypted data
                }
            }
        }
    }
}
