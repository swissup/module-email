<?php

namespace Swissup\Email\Mail\Message;

use Magento\Framework\Mail\MessageInterface;
use Swissup\Email\Mail\Message\Zend1FakeTransport;

class Convertor
{
    /**
     * \Mageno\Mail\Message => \Zend\Mail\Message
     *
     * @param  MessageInterface $message
     * @return \Zend\Mail\Message
     */
    public static function fromMessage(MessageInterface $message)
    {
        if ($message instanceof \Zend_Mail) {
            $message = self::fromZendMail1($message);
        } elseif ($message instanceof \Zend\Mail\Message) {
            $message = $message;
        } else {
            $message = \Zend\Mail\Message::fromString($message->getRawMessage());
        }

        return $message;
    }

    /**
     * \Zend_Mail => \Zend\Mail\Message
     *
     * @param  \Zend_Mail $zend1MailMessage
     * @return \Zend\Mail\Message
     */
    public static function fromZendMail1(\Zend_Mail $zend1MailMessage)
    {

        if (!$zend1MailMessage instanceof \Zend_Mail) {
            throw new \InvalidArgumentException('The message should be an instance of \Zend_Mail');
        }

        $zend2MailMessage = new \Zend\Mail\Message();
        $charset = $zend1MailMessage->getCharset() ?: 'utf-8';
        $zend2MailMessage->setEncoding($charset);

        $fakeTransport = new Convertor\Zend1FakeTransport();
        $fakeTransport->send($zend1MailMessage);
        $rawZend1MailMessage = $fakeTransport->getRawMessage();

        $boundary = $fakeTransport->boundary;
        $mimeMessage = \Zend\Mime\Message::createFromMessage($rawZend1MailMessage, $boundary);
        $mime = new \Zend\Mime\Mime($boundary);
        $mimeMessage->setMime($mime);

        $zend2MailMessage->setBody($mimeMessage);

        $headers = $zend2MailMessage->getHeaders();
        $headersEncoding = $zend1MailMessage->getHeaderEncoding();
        $headers->setEncoding($headersEncoding);
        if ($mimeMessage->isMultiPart()) {
            $headerName = 'content-type';
            if ($headers->has($headerName)) {
                /** @var ContentType $header */
                $headers->removeHeader($headerName);
            }
        }

        $_headers = \Zend\Mail\Headers::fromString($fakeTransport->header);
        $_headers->setEncoding($headersEncoding);
        $headers->addHeaders($_headers);

        $zend2MailMessage->setHeaders($headers);

        return $zend2MailMessage;
    }
}
