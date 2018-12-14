<?php
namespace Swissup\Email\Model\Transport;

use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\TransportInterface;

use Swissup\Email\Api\Data\ServiceInterface;
use Zend\Mail\Transport\SmtpOptions;
use Zend\Mail\Message;

class Smtp extends \Zend\Mail\Transport\Smtp implements TransportInterface
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
            $connectionConfig = [
                'username' => $config['user'],
                'password' => $config['password'],
                // 'ssl' => 'ssl'
            ];
            $ssl = $this->getSsl($config['secure']);
            if ($ssl) {
                $connectionConfig['ssl'] = $ssl;
            }
            $options = new SmtpOptions(
                [
                    'host' => $config['host'],
                    'port' => $config['port'],
                    'connection_class' => $config['auth'],//'login',
                    'connection_config' => $connectionConfig
                ]
            );
        }
        $this->setOptions($options);

        $this->message = $message;
    }

    /**
     *
     * @param  int|string $secure
     * @return bool|string
     */
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
     * @inheritdoc
     */
    public function getMessage()
    {
        return $this->message;
    }
}
