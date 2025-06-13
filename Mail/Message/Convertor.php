<?php

declare(strict_types=1);

namespace Swissup\Email\Mail\Message;

use Laminas\Mail\Header\HeaderValue;
use Laminas\Mail\Header\HeaderWrap;
use Laminas\Mail\Headers;
use Laminas\Mail\Message as LaminasMessage;
use Laminas\Mime\Decode;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mime\Part as MimePart;
use Magento\Framework\Mail\EmailMessageInterface;
use Magento\Framework\Mail\MailMessageInterface;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\MimePart as MagentoMimePart;

class Convertor
{
    private const DEFAULT_ENCODING = 'utf-8';
    private const VALID_ENCODINGS = ['utf-8', 'ascii'];
    private const HEADER_NAMES = ['to', 'reply-to', 'from'];

    /**
     * Converts various message types to Laminas\Mail\Message
     */
    public function fromMessage(MessageInterface $message): LaminasMessage
    {
        $isRemoveDuplicateHeaders = true;
        $checkInvalidHeaders = true;

        $laminasMessage = $this->convertToLaminasMessage($message);

        if ($message instanceof LaminasMessage) {
            $isRemoveDuplicateHeaders = false;
        }

        if ($checkInvalidHeaders && $this->hasInvalidHeaders($laminasMessage)) {
            $isRemoveDuplicateHeaders = true;
        }

        if ($isRemoveDuplicateHeaders) {
            $this->sanitizeHeaders($laminasMessage);
        }

        return $laminasMessage;
    }

    /**
     * Converts message to Laminas format
     */
    private function convertToLaminasMessage(MessageInterface $message): LaminasMessage
    {
        if ($message instanceof LaminasMessage) {
            return $this->fixBodyParts($message);
        }

        if ($message instanceof EmailMessageInterface) {
            return LaminasMessage::fromString($message->toString());
        }

        if ($message instanceof MailMessageInterface) {
            return LaminasMessage::fromString($message->getRawMessage());
        }

        return LaminasMessage::fromString($message->toString());
    }

    /**
     * Checks for invalid headers
     */
    private function hasInvalidHeaders(LaminasMessage $message): bool
    {
        foreach (self::HEADER_NAMES as $headerName) {
            $header = $message->getHeaders()->get($headerName);
            if ($header && !HeaderValue::isValid($header->getFieldValue())) {
                return true;
            }
        }
        return false;
    }

    /**
     * Sanitizes and fixes message headers
     */
    private function sanitizeHeaders(LaminasMessage $message): void
    {
        try {
            $headers = $message->getHeaders();
            $validHeadersArray = $this->processHeaders($headers->toArray());

            $uniqueHeaders = new Headers();
            $uniqueHeaders->setEncoding(self::DEFAULT_ENCODING);
            $uniqueHeaders->addHeaders($validHeadersArray);

            $message->setHeaders($uniqueHeaders);
        } catch (\Exception $e) {
            throw new \RuntimeException('Error processing headers: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Processes headers array
     * @param array<string, string> $headersArray
     * @return array<string, string>
     */
    private function processHeaders(array $headersArray): array
    {
        $validHeadersArray = [];
        foreach ($headersArray as $headerKey => $headerValue) {
            $headerValue = HeaderValue::filter($headerValue);

            if (!HeaderValue::isValid($headerValue) && HeaderWrap::canBeEncoded($headerValue)) {
                $headerValue = HeaderWrap::mimeEncodeValue($headerValue, self::DEFAULT_ENCODING);
            }

            $validHeadersArray[$headerKey] = $headerValue;
        }
        return $validHeadersArray;
    }

    /**
     * Converts Magento MimePart to Laminas MimePart
     */
    public function toMimePart(MagentoMimePart $part): MimePart
    {
        $mimePart = new MimePart($part->getRawContent());
        $this->copyMimePartProperties($part, $mimePart);

        return $mimePart;
    }

    /**
     * Copies properties from Magento MimePart to Laminas MimePart
     */
    private function copyMimePartProperties(MagentoMimePart $from, MimePart $to): void
    {
        $to->setType($from->getType());
        $to->setEncoding($from->getEncoding());
        $to->setFilters($from->getFilters());

        if ($charset = $from->getCharset()) {
            $to->setCharset($charset);
        }

        if ($from->isStream()) {
            $to->setIsStream(true);
        }
    }

    /**
     * Fixes message body parts
     */
    private function fixBodyParts(LaminasMessage $message): LaminasMessage
    {
        $body = $message->getBody();
        if ($body instanceof MimeMessage) {
            $parts = array_map(
                fn($part) => $part instanceof MagentoMimePart ? $this->toMimePart($part) : $part,
                $body->getParts()
            );
            $body->setParts($parts);
        }

        return $message;
    }
}
