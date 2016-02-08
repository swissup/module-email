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

        $this->message = $this->convertMail($message);

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

    protected function convertMail($mail)
    {
        //convert zend_mail1 to zend\mail\message
        // \Zend_Debug::dump($mail->getFrom());
        // \Zend_Debug::dump(get_class_methods($mail));
        // \Zend_Debug::dump($mail->getHeader('To'));
        // \Zend_Debug::dump($mail->getHeaders());

        $headers = new \Zend\Mail\Headers();

        $_headers = [];
        foreach ($mail->getHeaders() as $headerName => $values) {
            foreach ($values as $key => $value) {
                if ($key !== 'append') {
                    $_headers[$headerName][$key] = $value;
                }
            }
        }
        $headers->addHeaders($_headers);

        $headersEncoding = $mail->getHeaderEncoding();
        $headers->setEncoding($headersEncoding);

        $_message = new \Zend\Mail\Message();

        $_message->setHeaders($headers);

        $body = new \Zend\Mime\Message();
        $charset = $mail->getCharset();

        $text = $mail->getBodyText();

        if (!empty($text)) {
            if ($text instanceof \Zend_Mime_Part) {
                $part = new \Zend\Mime\Part($text->getContent());
                $part->encoding = $text->encoding;
                $part->type = $text->type;
                $part->charset = $text->charset;
            } elseif (is_string($text)) {
                $part = new \Zend\Mime\Part($text);
                $part->encoding = \Zend\Mime\Mime::ENCODING_QUOTEDPRINTABLE;
                $part->type = \Zend\Mime\Mime::TYPE_TEXT;
                $part->charset = $charset;
            }
            $body->addPart($part);
        }

        $html = $mail->getBodyHtml();
        if (!empty($html)) {
            if ($html instanceof \Zend_Mime_Part) {
                $part = new \Zend\Mime\Part($html->getContent());
                $part->encoding = $html->encoding;
                $part->type = $html->type;
                $part->charset = $html->charset;
            } elseif (is_string($html)) {
                $part = new \Zend\Mime\Part($html);
                $part->encoding = \Zend\Mime\Mime::ENCODING_QUOTEDPRINTABLE;
                $part->type = \Zend\Mime\Mime::TYPE_TEXT;
                $part->charset = $charset;
            }

            $body->addPart($part);
        }
        //@todo $mail->getParts() copy attachments

        $_message->setBody($body);
        // \Zend_Debug::dump($_message);
        // die;
        return $_message;
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
            // \Zend_Debug::dump(__LINE__);
            // die;
        } catch (\Exception $e) {
            $phrase = new \Magento\Framework\Phrase($e->getMessage());
            throw new \Magento\Framework\Exception\MailException($phrase, $e);
        }
        return true;
    }
}
