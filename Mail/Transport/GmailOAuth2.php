<?php
namespace Swissup\Email\Mail\Transport;

use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\TransportInterface;
use Laminas\Mail\Transport\SmtpOptions;
use Swissup\Email\Api\Data\ServiceInterface;

class GmailOAuth2 extends \Laminas\Mail\Transport\Smtp implements TransportInterface
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
            $port = 587;

            if (!isset($config['token']) || !isset($config['token']['access_token'])) {
                $phrase = new \Magento\Framework\Phrase('token is broken');
                throw new \Magento\Framework\Exception\MailException($phrase);
            }
            $tokenOptions = $config['token'];
            if (time() > $tokenOptions['expires']) {
                $phrase = new \Magento\Framework\Phrase('access token is expired');
                throw new \Magento\Framework\Exception\MailException($phrase);
            }

            $options = new SmtpOptions(
                [
                    'host' => $host,
                    'port' => $port,
                    'connection_class' => 'xoauth2',
                    'connection_config' =>
                    [
                        'username' => $config['email'],
                        'access_token' => $tokenOptions['access_token'],
                        'ssl' => 'tls'
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

            parent::send($message);
        } catch (\Exception $e) {
            $phrase = new \Magento\Framework\Phrase($e->getMessage());
            throw new \Magento\Framework\Exception\MailException($phrase, $e);
        }
        return true;
    }

    /**
     *
     * @return MessageInterface
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
