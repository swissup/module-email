<?php
namespace Swissup\Email\Model\Transport;

use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\TransportInterface;

use Swissup\Email\Api\Data\ServiceInterface;

class Smtp extends \Zend_Mail_Transport_Smtp implements TransportInterface
{
    /**
     * @var MessageInterface
     */
    protected $message;

    /**
     *
     * @param MessageInterface $message
     * @param array $config
     * @throws \InvalidArgumentException
     */
    public function __construct(
        MessageInterface $message,
        array $config
    ) {
        if (!$message instanceof \Zend_Mail) {
            throw new \InvalidArgumentException('The message should be an instance of \Zend_Mail');
        }

        $host = $config['host'];
        $options = [
           'auth'     => $config['auth'],
           'username' => $config['user'],
           'password' => $config['password'],
           'port'     => $config['port']
        ];
        $ssl = $this->getSsl($config['secure']);
        if ($ssl) {
            $options['ssl'] = $ssl;
        }

        parent::__construct($host, $options);
        $this->message = $message;
    }

    protected function getSsl($secure)
    {
        // $secure = $this->getSecure();
        if (ServiceInterface::SECURE_SSL == $secure) {
            return 'SSL';
        } elseif (ServiceInterface::SECURE_TLS == $secure) {
            return 'TLS';
        }
        return false;
    }

    /**
     * Send a mail using this transport
     *
     * @return void
     * @throws \Magento\Framework\Exception\MailException
     */
    public function sendMessage()
    {
        try {
            parent::send($this->message);
        } catch (\Exception $e) {
            $phrase = new \Magento\Framework\Phrase($e->getMessage());
            throw new \Magento\Framework\Exception\MailException($phrase, $e);
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getMessage()
    {
        return $this->message;
    }
}
