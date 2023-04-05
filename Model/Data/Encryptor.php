<?php
namespace Swissup\Email\Model\Data;

use Swissup\Email\Api\EncryptorInterface;

class Encryptor implements EncryptorInterface
{
    const PREFIX = 'encrypted:';

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    private $encryptor;

    /**
     * Constructor
     *
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     */
    public function __construct(
        \Magento\Framework\Encryption\EncryptorInterface $encryptor
    ) {
        $this->encryptor = $encryptor;
    }

    private function isEncryptionEnabled()
    {
        return true;
    }

    /**
     * Encrypt a string
     *
     * @param string $data
     * @return string
     */
    public function encrypt($data)
    {
        $isEncryptionEnabled = $this->isEncryptionEnabled();
        $data = (string) $data;
        if ($isEncryptionEnabled) {
            $data = self::PREFIX . $this->encryptor->encrypt($data);
        }
        return $data;
    }

    /**
     * @param string $data
     * @return bool
     */
    private function isEncryptedData(string $data)
    {
        return strpos($data, self::PREFIX) === 0;
    }

    /**
     * Decrypt a string
     *
     * @param string $data
     * @return string
     */
    public function decrypt($data)
    {
        $isEncryptionEnabled = $this->isEncryptionEnabled();
        if ($isEncryptionEnabled) {
            $data = (string) $data;
            $prefix = self::PREFIX;
            if ($this->isEncryptedData($data)) {
                $data = substr($data, strlen($prefix));
                $data = $this->encryptor->decrypt($data);
            }
        }

        return $data;
    }
}
