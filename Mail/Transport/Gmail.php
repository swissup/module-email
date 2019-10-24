<?php
namespace Swissup\Email\Mail\Transport;

use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\TransportInterface;

use Swissup\Email\Api\Data\ServiceInterface;
use Swissup\Email\Mail\Message\Convertor;

use Zend\Mail\Transport\SmtpOptions;

class Gmail extends \Zend\Mail\Transport\Smtp implements TransportInterface
{
    /**
     * @var MessageInterface
     */
    protected $message;

    /**
     * Constructor.
     *
     * @param array $config
     * @param  SmtpOptions $options Optional
     */
    public function __construct(
        array $config,
        SmtpOptions $options = null
    ) {
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
            $message = Convertor::fromMessage($message);

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

    /**
     *
     * @param MessageInterface $message
     */
    public function setMessage($message)
    {
        // if (!$message instanceof MessageInterface) {
        //     throw new \InvalidArgumentException('The message should be an instance of \Magento\Framework\Mail\Message');
        // }
        $this->message = $message;
        return $this;
    }
}
