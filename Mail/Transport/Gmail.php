<?php
namespace Swissup\Email\Mail\Transport;

use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\TransportInterface;

use Swissup\Email\Api\Data\ServiceInterface;
use Zend\Mail\Transport\SmtpOptions;
use Zend\Mail\Message;

class Gmail extends \Zend\Mail\Transport\Smtp implements TransportInterface
{
    /**
     * @var MessageInterface
     */
    protected $message;

    /**
     * Constructor.
     *
     * @param MessageInterface $message
     * @param array $config
     * @param  SmtpOptions $options Optional
     */
    public function __construct(
        MessageInterface $message,
        array $config,
        SmtpOptions $options = null
    ) {
        if (!$message instanceof MessageInterface) {
            throw new \InvalidArgumentException('The message should be an instance of \Magento\Framework\Mail\Message');
        }

        if (! $options instanceof SmtpOptions) {
            $host = 'smtp.gmail.com';
            $port = 465;

            $options = new SmtpOptions(
                [
                    'host' => $host,
                    'port' => $port,
                    'connection_class' => 'login',
                    'connection_config' =>
                    [
                        'username' => $config['user'],
                        'password' => $config['password'],
                        'ssl' => 'ssl'
                    ]
                ]
            );
        }
        $this->setOptions($options);

        $this->message = $message;
    }

    /**
     * Send a mail using this transport
     *
     * @return boolean
     * @throws \Magento\Framework\Exception\MailException
     */
    public function sendMessage()
    {
        try {
            $message = $this->message;
            $message = Message::fromString($message->getRawMessage());

            parent::send($message);
        } catch (\Exception $e) {
            $phrase = new \Magento\Framework\Phrase($e->getMessage());
            throw new \Magento\Framework\Exception\MailException($phrase, $e);
        }
        return true;
    }

    /**
     *
     * @return \Magento\Framework\Mail\Message
     */
    public function getMessage()
    {
        return $this->message;
    }
}
