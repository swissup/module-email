<?php
namespace Swissup\Email\Model\Transport;

use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\TransportInterface;

use Swissup\Email\Api\Data\ServiceInterface;

use SlmMail\Mail\Transport\HttpTransport;
use SlmMail\Service\MandrillService;

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
        if (!$message instanceof \Zend_Mail) {
            throw new \InvalidArgumentException('The message should be an instance of \Zend_Mail');
        }

        $this->message = $this->convertMail($message);

        $service = new MandrillService($config['password']);
        $service->pingUser();

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
            $this->transport->send($this->message);
        } catch (\Exception $e) {
            $phrase = new \Magento\Framework\Phrase($e->getMessage());
            throw new \Magento\Framework\Exception\MailException($phrase, $e);
        }
        return true;
    }

    protected function convertMail($mail)
    {
        //convert zend_mail1 to zend\mail\message

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
}
