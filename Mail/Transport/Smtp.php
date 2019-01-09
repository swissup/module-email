<?php
namespace Swissup\Email\Mail\Transport;

use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\TransportInterface;

use Swissup\Email\Api\Data\ServiceInterface;
use Swissup\Email\Mail\Message\Convertor;
use Swissup\Email\Model\Service;

use Zend\Mail\Transport\SmtpOptions;

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
            $connectionConfig = [];
            if ($config['auth'] != Service::AUTH_TYPE_NONE) {
                $connectionConfig = [
                    'username' => $config['user'],
                    'password' => $config['password']
                ];
            }
            $ssl = $this->getSsl($config['secure']);
            if ($ssl) {
                $connectionConfig['ssl'] = $ssl;
            }
            $options = new SmtpOptions(
                [
                    'host' => $config['host'],
                    'port' => $config['port'],
                    'connection_config' => $connectionConfig
                ]
            );
            if ($config['auth'] != Service::AUTH_TYPE_NONE) {
                $options->setConnectionClass($config['auth']);
            }
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
            $message = Convertor::fromMessage($message);

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
