<?php
namespace Swissup\Email\Mail\Transport;

use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\TransportInterface;

use SlmMail\Mail\Transport\HttpTransport;
use SlmMail\Service\MandrillService;

use Swissup\Email\Api\Data\ServiceInterface;
use Swissup\Email\Mail\Message\Convertor;

class Mandrill implements TransportInterface
{
    /**
     * @var MessageInterface
     */
    protected $message;

    /**
     * @var HttpTransport
     */
    protected $transport;

    /**
     *
     * @param MessageInterface $message
     * @param array $config
     * @ param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param SesClient $client
     * @throws \InvalidArgumentException
     */
    public function __construct(
        MessageInterface $message,
        array $config
    ) {
        $this->message = $message;

        $service = new MandrillService($config['password']);
        // \Zend_Debug::dump($service->pingUser()));

        $this->transport = new HttpTransport($service);
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

            $this->transport->send($message);
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
