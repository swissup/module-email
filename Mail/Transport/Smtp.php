<?php
namespace Swissup\Email\Mail\Transport;

use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\TransportInterface;
use Swissup\Email\Api\Data\ServiceInterface;
use Swissup\Email\Model\Service;
use Laminas\Mail\Transport\SmtpOptions;

class Smtp extends \Laminas\Mail\Transport\Smtp implements TransportInterface
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
            if (!empty($config['sending_host'])) {
                $options->setName($config['sending_host']);
            }
        }
        $this->setOptions($options);
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

            parent::send($message);
        } catch (\Exception $e) {
            $phrase = new \Magento\Framework\Phrase($e->getMessage());
            throw new \Magento\Framework\Exception\MailException($phrase, $e);
        }
    }

    /**
     * @inheritdoc
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
        //     throw new \InvalidArgumentException(
        //         'The message should be an instance of \Magento\Framework\Mail\Message'
        //     );
        // }
        $this->message = $message;
        return $this;
    }
}
