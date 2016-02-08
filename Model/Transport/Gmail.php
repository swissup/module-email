<?php
namespace Swissup\Email\Model\Transport;

use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\TransportInterface;

use Swissup\Email\Api\Data\ServiceInterface;

class Gmail extends \Zend_Mail_Transport_Smtp implements TransportInterface
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

        $host = 'smtp.gmail.com';
        $options = [
           'username' => $config['user'],
           'password' => $config['password'],
           'auth'     => 'login',
           'port'     => 465,
           'ssl'      => 'SSL'
        ];

        parent::__construct($host, $options);
        $this->message = $message;
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
}
