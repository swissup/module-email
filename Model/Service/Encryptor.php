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
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param EncryptorInterface $encryptor
     */
    public function __construct(EncryptorInterface $encryptor, SerializerInterface $serializer)
    {
        $this->encryptor = $encryptor;
        $this->serializer = $serializer;
    }

    /**
     * @return string[]
     */
    private function getAttributes()
    {
        return ['password', 'token'];
    }

    /**
     * @return string[]
     */
    private function getSerializableAttributes()
    {
        return ['token'];
    }

    /**
     * @param $attribute
     * @return bool
     */
    private function isSerializableAttribute($attribute)
    {
        return in_array($attribute, $this->getSerializableAttributes());
    }

    /**
     * @param DataObject $object
     * @return void
     */
    public function encrypt(ServiceInterface $object): void
    {
        foreach ($this->getAttributes() as $attributeCode) {
            $value = $object->getData($attributeCode);
            if ($this->isSerializableAttribute($attributeCode)) {
                $value = $this->serializer->serialize($value);
            }
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
                    if ($this->isSerializableAttribute($attributeCode)) {
                        $value = $this->serializer->unserialize($value);
                    }
                    $object->setData($attributeCode, $value);
                } catch (\Exception $e) {
                    // value is not encrypted or something wrong with encrypted data
                }
            }
        }
    }
}
