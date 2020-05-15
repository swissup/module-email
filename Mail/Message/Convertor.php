<?php

namespace Swissup\Email\Mail\Message;

use Magento\Framework\Mail\MessageInterface;
use Swissup\Email\Mail\Message\Zend1FakeTransport;

class Convertor
{
    /**
     * \Magento\Mail\Message => \Zend\Mail\Message
     *
     * @param  MessageInterface $message
     * @return \Zend\Mail\Message
     */
    public static function fromMessage($message)
    {
        if ($message instanceof \Zend_Mail) {
            $message = self::fromZendMail1($message);
        } elseif ($message instanceof \Zend\Mail\Message) {
            $message = $message;
        } elseif ($message instanceof \Magento\Framework\Mail\EmailMessageInterface) {
            $message = self::fromMagentoEmailMessage($message);
        } elseif ($message instanceof \Magento\Framework\Mail\MailMessageInterface) {
            $message = \Zend\Mail\Message::fromString($message->getRawMessage());
        } else {
            $message = \Zend\Mail\Message::fromString($message->toString());
        }

        //Ignore encoding exceptions in headers
        // try {
        $headers = $message->getHeaders();
        $uniqueHeaders = new \Zend\Mail\Headers();
        $uniqueHeaders->addHeaders($headers->toArray());
        $message->setHeaders($uniqueHeaders);
        // } catch (\Exception $e) {
        // }

        return $message;
    }

    /**
     *
     * @param \Magento\Framework\Mail\EmailMessage $magentoEmailMessage
     * @return \Zend\Mail\Message
     */
    private static function fromMagentoEmailMessage($magentoEmailMessage)
    {
        $encoding = $magentoEmailMessage->getEncoding() ?: 'utf-8';

        if (!in_array(strtolower($encoding), ['utf-8', 'ascii'])) {
            return \Zend\Mail\Message::fromString(
                $magentoEmailMessage->toString()
            );
        }

        $rawMessage = $magentoEmailMessage->toString(); //dosn't work properly return Mime::encoded body part

        /** @var \Zend\Mail\Message $zend2MailMessage */
        $zend2MailMessage = new \Zend\Mail\Message();
        $zend2MailMessage->setEncoding($encoding);

        // @see \Zend\Mail\Message::fromString($mailString);
        /** @var \Zend\Mail\Headers $headers */
        $headers = null;
        $content = null;
        \Zend\Mime\Decode::splitMessage($rawMessage, $headers, $content, \Zend\Mail\Headers::EOL);
        if ($headers->has('mime-version')) {
            // todo - restore body to mime\message
        }
        $headers->setEncoding($encoding);
        $zend2MailMessage->setHeaders($headers);

        $messageBodyParts = $magentoEmailMessage->getBody()->getParts();
        $messageBodyPart = reset($messageBodyParts);
        $content = $messageBodyPart->getRawContent(); // instead of getContent()

        $part = new \Zend\Mime\Part($content);
        $part->setCharset($messageBodyPart->getCharset());

        $partEncoding = $messageBodyPart->getEncoding() ?: \Zend\Mime\Mime::ENCODING_8BIT;
        $part->setEncoding($partEncoding);

        $part->setDisposition($messageBodyPart->getDisposition());
        $part->setType($messageBodyPart->getType());

        $mimeMessage = new \Zend\Mime\Message();
        $mimeMessage->addPart($part);

        $zend2MailMessage->setBody($mimeMessage);

        return $zend2MailMessage;
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

        if (!$bodyText = $zend1MailMessage->getBodyText()) {
            $bodyText = $zend1MailMessage->getBodyHtml(true);
            $bodyText = strip_tags($bodyText);
            $zend1MailMessage->setBodyText($bodyText);
        }

        $zend2MailMessage = new \Zend\Mail\Message();
        $charset = $zend1MailMessage->getCharset() ?: 'utf-8';
        $zend2MailMessage->setEncoding($charset);

        $fakeTransport = new Convertor\Zend1FakeTransport();
        $fakeTransport->send($zend1MailMessage);
        $rawZend1MailMessage = $fakeTransport->toString();

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
