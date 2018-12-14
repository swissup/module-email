<?php
namespace Swissup\Email\Model\Transport;

use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\TransportInterface;

use Zend\Mail\Message;

class Sendmail extends \Zend\Mail\Transport\Sendmail implements TransportInterface
{
    /**
     * @var MessageInterface
     */
    protected $message;

    /**
     * @param MessageInterface $message
     * @param null $parameters
     * @throws \InvalidArgumentException
     */
    public function __construct(MessageInterface $message, $parameters = null)
    {
        if (!$message instanceof MessageInterface) {
            throw new \InvalidArgumentException('The message should be an instance of \Magento\Framework\Mail\Message');
        }

        parent::__construct($parameters);
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
            $message = $this->message;
            $message = Message::fromString($message->getRawMessage());
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
}
