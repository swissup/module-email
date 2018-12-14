<?php
namespace Swissup\Email\Model\Transport;

abstract class SlmAbstract
{
    protected function convertMailMessage($mail)
    {
        if (!$mail instanceof \Zend_Mail) {
            throw new \InvalidArgumentException('The message should be an instance of \Zend_Mail');
        }
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
            $part = false;
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
            if ($part) {
                $body->addPart($part);
            }
        }

        $html = $mail->getBodyHtml();
        if (!empty($html)) {
            $part = false;
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

            if ($part) {
                $body->addPart($part);
            }
        }
        //@todo $mail->getParts() copy attachments

        $_message->setBody($body);
        // \Zend_Debug::dump($_message);
        // die;
        return $_message;
    }
}
