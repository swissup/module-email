<?php
namespace Swissup\Email\Mail\Transport;

use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\TransportInterface;

use Swissup\Email\Mail\Message\Convertor;

class Sendmail extends \Zend\Mail\Transport\Sendmail implements TransportInterface
{
    /**
     * @var MessageInterface
     */
    protected $message;

    /**
     * @param null $parameters
     * @throws \InvalidArgumentException
     */
    public function __construct($parameters = null)
    {
        parent::__construct($parameters);
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
            throw new \Magento\Framework\Exception\MailException(new \Magento\Framework\Phrase($e->getMessage()), $e);
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
        //     throw new \InvalidArgumentException('The message should be an instance of \Magento\Framework\Mail\Message');
        // }
        $this->message = $message;
        return $this;
    }
}
