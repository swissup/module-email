<?php

namespace Swissup\Email\Mail\Message;

use Magento\Framework\Mail\MessageInterface;

class Convertor
{
    /**
     * \Magento\Mail\Message => \Laminas\Mail\Message
     *
     * @param  MessageInterface $message
     * @return \Laminas\Mail\Message
     */
    public function fromMessage($message)
    {
        $isRemoveDuplicateHeaders = true;
        $checkInvalidHeaders = true;
        if ($message instanceof \Laminas\Mail\Message) {
            $message = $message;
            $message = $this->fixBodyParts($message);
            $isRemoveDuplicateHeaders = false;
        } elseif ($message instanceof \Zend\Mail\Message) {
            $message = $message;
            $message = $this->fixBodyParts($message);
            $isRemoveDuplicateHeaders = false;
        } else if ($message instanceof \Swissup\Email\Mail\EmailMessage) {
            $message = $message->getZendMessage();
            $checkInvalidHeaders = false;
            $isRemoveDuplicateHeaders = false;
        } elseif ($message instanceof \Magento\Framework\Mail\EmailMessageInterface) {
            //fix for desposition https://github.com/magento/magento2/commit/6976aabdfdab91a9d06e412c2ed619538ed034b6
            $message = \Laminas\Mail\Message::fromString($message->toString());
            // $message = self::fromMagentoEmailMessage($message);
        } elseif ($message instanceof \Magento\Framework\Mail\MailMessageInterface) {
            $message = \Laminas\Mail\Message::fromString($message->getRawMessage());
        } else {
            $message = \Laminas\Mail\Message::fromString($message->toString());
        }

        $hasInvalidHeader = false;
        if ($checkInvalidHeaders) {
            array_map(function ($headerName) use ($message, &$hasInvalidHeader) {
                $header = $message->getHeaders()->get($headerName);
                if ($header
                    && !\Zend\Mail\Header\HeaderValue::isValid($header->getFieldValue())
                ) {
                    $hasInvalidHeader = true;
                }
            }, ['to', 'reply-to', 'from']);
        }

        if ($isRemoveDuplicateHeaders || $hasInvalidHeader) {
            //Ignore encoding exceptions in headers
            $ignoreException = false;
            try {
                $headers = $message->getHeaders();
                $headersArray = $headers->toArray();
                $validHeadersArray = [];
                $encoding = 'utf-8';
                foreach ($headersArray as $headerKey => $headerValue) {
                    $headerValue = \Zend\Mail\Header\HeaderValue::filter(
                        $headerValue
                    );
                    if (!\Zend\Mail\Header\HeaderValue::isValid($headerValue)
                        && \Zend\Mail\Header\HeaderWrap::canBeEncoded($headerValue)
                    ) {
                        $headerValue = \Zend\Mail\Header\HeaderWrap::mimeEncodeValue(
                            $headerValue,
                            $encoding
                        );
                    }

                    $validHeadersArray[$headerKey] = $headerValue;
                }
                $uniqueHeaders = new \Zend\Mail\Headers();
                $uniqueHeaders->setEncoding($encoding);
                $uniqueHeaders->addHeaders($validHeadersArray);
                $message->setHeaders($uniqueHeaders);
            } catch (\Exception $e) {
                if (!$ignoreException) {
                    throw $e;
                }
            }
        }
        return $message;
    }

    /**
     *
     * @param \Magento\Framework\Mail\EmailMessage $magentoEmailMessage
     * @return \Zend\Mail\Message
     */
    private function fromMagentoEmailMessage($magentoEmailMessage)
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
//        if ($headers->has('mime-version')) {
//            // todo - restore body to mime\message
//        }
        $headers->setEncoding($encoding);
        //https://github.com/laminas/laminas-mail/issues/22
        //https://github.com/magento/magento2/issues/26849
        // $headersArray = $headers->toArray();
        // $headers->clearHeaders();

        // if (isset($headersArray['Subject'])) {
        //     $headersArray['Subject'] =

        //     \Zend\Mail\Header\HeaderWrap::mimeEncodeValue(
        //         iconv("utf-8","ascii//TRANSLIT", $headersArray['Subject']),
        //         $encoding
        //     );
        //     // \Zend\Mime\Mime::encodeBase64Header(
        //     //     $headersArray['Subject'],
        //     //     $encoding
        //     // );
        // }
        // $headers->addHeaders($headersArray);
        $zend2MailMessage->setHeaders($headers);

        $messageBodyParts = $magentoEmailMessage->getBody()->getParts();
        $messageBodyPart = reset($messageBodyParts);
        $content = $messageBodyPart->getRawContent(); // instead of getContent()

        $part = new \Zend\Mime\Part($content);
        $part->setCharset($messageBodyPart->getCharset());

        $partEncoding = $messageBodyPart->getEncoding() ?: \Zend\Mime\Mime::ENCODING_8BIT;
        $part->setEncoding($partEncoding);

        //https://github.com/magento/magento2/issues/25076#issuecomment-622501468
        $desposition = $messageBodyPart->getDisposition();
        // $desposition = \Magento\Framework\Mail\MimeInterface::DISPOSITION_INLINE;
        if ($desposition) {
            $part->setDisposition($desposition);
        }

        $type = $messageBodyPart->getType();
        if ($type) {
            $part->setType($type);
        }

        $mimeMessage = new \Zend\Mime\Message();
        $mimeMessage->addPart($part);

        $zend2MailMessage->setBody($mimeMessage);

        return $zend2MailMessage;
    }


    /**
     * @param \Magento\Framework\Mail\MimePart $part
     * @return \Laminas\Mime\Part
     */
    public function toMimePart(\Magento\Framework\Mail\MimePart $part): \Laminas\Mime\Part
    {
//        $propertyName = 'mimePart';
//        $reflectionClass = new \ReflectionClass($part);
//        $property = $reflectionClass->getProperty((string) $propertyName);
//        $property->setAccessible(true);
//        $value = $property->getValue($part);
//
//        return $value;

        $mimePart = new \Laminas\Mime\Part($part->getRawContent());
        $mimePart->setType($part->getType());
        $mimePart->setEncoding($part->getEncoding());
        $mimePart->setFilters($part->getFilters());
//        $boundary = $part->getBoundary();
//        if ($boundary) {
//            $mimePart->setBoundary($boundary);
//        }
        $charset = $part->getCharset();
        if ($charset) {
            $mimePart->setCharset($charset);
        }
//        $disposition = $part->getDisposition();
//        if ($disposition) {
//            $mimePart->setDisposition($disposition);
//        }
//        $description = $part->getDescription();
//        if ($description) {
//            $mimePart->setDescription($description);
//        }
//        $fileName = $part->getFileName();
//        if ($fileName) {
//            $mimePart->setFileName($fileName);
//        }
//        $location = $part->getLocation();
//        if ($location) {
//            $mimePart->setLocation($location);
//        }
//        $language = $part->getLanguage();
//        if ($language) {
//            $mimePart->setLanguage($language);
//        }
        $isStream = $part->isStream();
        if ($isStream) {
            $mimePart->setIsStream($isStream);
        }

        return $mimePart;
    }

    /**
     * @param $message
     * @return mixed
     */
    private function fixBodyParts($message)
    {
        $body = $message->getBody();
        if ($body instanceof \Laminas\Mime\Message) {
            $parts = [];
            foreach ($body->getParts() as $part) {
                if ($part instanceof \Magento\Framework\Mail\MimePart) {
                    $parts[] = self::toMimePart($part);
                } elseif ($part instanceof \Laminas\Mime\Part) {
                    $parts[] = $part;
                }
           }
           $body->setParts($parts);
        }

        return $message;
    }
}
