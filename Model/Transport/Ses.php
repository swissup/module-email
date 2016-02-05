<?php
namespace Swissup\Email\Model\Transport;

use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\TransportInterface;

use Swissup\Email\Api\Data\ServiceInterface;

use SlmMail\Mail\Transport\HttpTransport;
use SlmMail\Service\SesService;

use Aws\Ses\SesClient;
use Aws\Credentials\Credentials;

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
     * @ param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param SesClient $client
     * @throws \InvalidArgumentException
     */
    public function __construct(
        MessageInterface $message,
        array $config
    ) {
        if (!$message instanceof \Zend_Mail) {
            throw new \InvalidArgumentException('The message should be an instance of \Zend_Mail');
        }

        //convert zend_mail1 to zend\mail\message
        \Zend_Debug::dump($message->getFrom());
        // \Zend_Debug::dump($message->getTo());
        // \Zend_Debug::dump($message->getHeaders());
        // $headers = new \Zend\Mail\Headers();
        // \Zend_Debug::dump($headers);
        // $headers->addHeaders($message->getHeaders());
        // \Zend_Debug::dump($headers);
        $_message = new \Zend\Mail\Message();
        $_message->addFrom("alex@templates-master.com", "alex")
            ->addTo("alex@templates-master.com")
            ->setSubject("Sending an email from Zend\Mail! aws ");
        $_message->setBody("This is the message body.");
        // $_message->setHeaders($headers);
        // $_message->addFrom()
        // die;

        $this->message = $_message;

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
     * @return void
     * @throws \Magento\Framework\Exception\MailException
     */
    public function sendMessage()
    {
        try {
            // \Zend_Debug::dump($this->message);
            // \Zend_Debug::dump($this->message->getHeaders());
            // die;
            $this->transport->send($this->message);
            // die;
        } catch (\Exception $e) {
            $phrase = new \Magento\Framework\Phrase($e->getMessage());
            throw new \Magento\Framework\Exception\MailException($phrase, $e);
        }
        return true;
    }
}
