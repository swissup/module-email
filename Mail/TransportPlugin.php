<?php
namespace Swissup\Email\Mail;

use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Mail\TransportInterface;


use Swissup\Email\Mail\Transport;

class TransportPlugin
{
    /**
     *
     * @var Transport
     */
    protected $transport;

    /**
     * @var MessageInterface|null
     */
    private $message;

    /**
     * @var bool
     */
    private $once = false;

    /**
     *
     * @param Transport $transport
     * @throws \InvalidArgumentException
     */
    public function __construct(Transport $transport)
    {
        $this->transport = $transport;
    }

    /**
     * @param TransportInterface $subject
     * @param \Closure $proceed
     * @throws \Exception
     *
     * @return void
     */
    public function aroundSendMessage(
        TransportInterface $subject,
        \Closure $proceed
    ) {
        if ($this->once === false) {
            $proceed();
            return;
        }
        $this->once = true;

        $this->message = $this->getMessage($subject);
        if (empty($this->message)) {
            $proceed();
            return;
        }

        $this->transport
            ->setMessage($this->message)
            ->sendMessage();
    }

    /**
     * @param $transport
     * @return mixed|null
     */
    protected function getMessage($transport)
    {
        if (method_exists($transport, 'getMessage')) {
            return $transport->getMessage();
        }

        try {
            $reflectionClass = new \ReflectionClass($transport);
            $messageProperty = $reflectionClass->getProperty('_message');
            $messageProperty->setAccessible(true);

            return $messageProperty->getValue($transport);
        } catch (\Exception $e) {
            return null;
        }
    }
}
