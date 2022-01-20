<?php
declare(strict_types=1);
/**
 * @author        Wilfried Wolf <wilfried.wolf@sandstein.de>
 */

namespace Swissup\Email\Mail;

use Laminas\Mail\Message;
use Magento\Framework\Mail\Exception\InvalidArgumentException;
use Magento\Framework\Mail\EmailMessage as FrameworkEmailMessage;
use Swissup\Email\Mail\EmailMessageInterface;

/**
 * Class EmailMessage
 * @package Swissup\Email\Mail
 */
class EmailMessage extends FrameworkEmailMessage implements EmailMessageInterface
{
    /**
     * @return Message
     */
    public function getZendMessage()
    {
        if (property_exists($this, 'zendMessage')) {
            return $this->zendMessage;
        }
        // 2.3 backward compatibility
        try {
            $message = $this->getPrivateParentPropertyValue('zendMessage');
            return $message;
        } catch (\ReflectionException $e) {
            throw new InvalidArgumentException($e->getMessage());
        }
        if (property_exists($this, 'message')) {
            return $this->message;
        }
        try {
            $message = $this->getPrivateParentPropertyValue('message');
            return $message;
        } catch (\ReflectionException $e) {
            throw new InvalidArgumentException($e->getMessage());
        }

        throw new InvalidArgumentException('The "zendMessage" property should exist in instance of EmailMessage');
    }

    /**
     * @param string $propertyName
     * @return mixed
     * @throws \ReflectionException
     */
    private function getPrivateParentPropertyValue($propertyName)
    {
        $reflectionClass = new \ReflectionClass($this);
        $parentReflectionClass = $reflectionClass->getParentClass();
        $property = $parentReflectionClass->getProperty((string) $propertyName);
        $property->setAccessible(true);
        $value = $property->getValue($this);

        return $value;
    }
}
