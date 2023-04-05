<?php
namespace Swissup\Email\Api;

use Swissup\Email\Api\Data\ServiceInterface;

interface ServiceEncryptorInterface
{
    /**
    * Encrypt a string
    *
    * @param string $data
    * @return string
    */
    public function encrypt(ServiceInterface $object): void;

    /**
    * Decrypt a string
    *
    * @param string $data
    * @return string
    */
    public function decrypt(ServiceInterface $object): void;
}
