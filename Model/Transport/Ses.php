<?php
namespace Swissup\Email\Model\Transport;

use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\TransportInterface;

use SlmMail\Mail\Transport\HttpTransport;
use SlmMail\Service\SesService;

use Aws\Ses\SesClient;
use Aws\Credentials\Credentials;

class Ses extends SlmAbstract implements TransportInterface
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
        $this->message = $this->convertMailMessage($message);

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
            // \Zend_Debug::dump($this->message);
            // \Zend_Debug::dump($this->message->getHeaders());
            // die;
            $this->transport->send($this->message);
            // \Zend_Debug::dump(__LINE__);
            // die;
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
