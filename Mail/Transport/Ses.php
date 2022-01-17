<?php
namespace Swissup\Email\Mail\Transport;

use Aws\Ses\SesClient;
use Aws\Credentials\Credentials;

use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\TransportInterface;

use SlmMail\Mail\Transport\HttpTransport;
use SlmMail\Service\SesService;

use Swissup\Email\Mail\Message\Convertor;

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
     * @param array $config
     * @throws \InvalidArgumentException
     */
    public function __construct(
        array $config
    ) {
        $credentials = new Credentials($config['user'], $config['password']);
        $client = SesClient::factory([
            'credentials' => $credentials,
            'region'  => 'us-east-1',//'us-west-2'
            'version' => '2010-12-01', //'latest'
            'timeout' => 10,
            // 'debug'   => true
            // 'http_adapter' => 'Zend\Http\Client\Adapter\Proxy'
        ]);
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
            $message = Convertor::fromMessage($message);
            $message = Convertor::fixBodyParts($message);

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
