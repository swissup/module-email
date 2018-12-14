<?php
namespace Swissup\Email\Mail\Transport;

use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\TransportInterface;

use SlmMail\Mail\Transport\HttpTransport;
use SlmMail\Service\SesService;

use Aws\Ses\SesClient;
use Aws\Credentials\Credentials;
use Zend\Mail\Message;

class Ses implements TransportInterface
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
     * @throws \InvalidArgumentException
     */
    public function __construct(
        MessageInterface $message,
        array $config
    ) {
        $this->message = $message;

        $credentials = new Credentials($config['user'], $config['password']);
        $client = SesClient::factory(array(
            'credentials' => $credentials,
            'region'  => 'us-east-1',//'us-west-2'
            'version' => '2010-12-01', //'latest'
            'timeout' => 10,
            // 'debug'   => true
            // 'http_adapter' => 'Zend\Http\Client\Adapter\Proxy'
        ));
        $service = new SesService($client);
        // \Zend_Debug::dump(get_class_methods($service));
        // \Zend_Debug::dump($service->getSendQuota());
        // \Zend_Debug::dump($service->getSendStatistics());
        // // die;
        $this->transport = new HttpTransport($service);
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
