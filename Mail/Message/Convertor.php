<?php

declare(strict_types=1);

namespace Swissup\Email\Mail\Message;

use InvalidArgumentException;
use RuntimeException;
use Throwable;
use Magento\Framework\Mail\EmailMessageInterface;
use Magento\Framework\Mail\MailMessageInterface;
use Magento\Framework\Mail\MessageInterface;
use Symfony\Component\Mime\Email as SymfonyMimeEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Part\DataPart;
use ZBateson\MailMimeParser\MailMimeParser;
use ZBateson\MailMimeParser\Header\HeaderConsts;
use ZBateson\MailMimeParser\Message as ParsedMessage;

class Convertor
{
    private const DEFAULT_CHARSET = 'UTF-8';
    private const CONTENT_TYPE_TEXT = 'text/plain';
    private const CONTENT_TYPE_HTML = 'text/html';

    private const HEADER_MAPPINGS = [
        HeaderConsts::FROM => 'from',
        HeaderConsts::TO => 'to',
        HeaderConsts::CC => 'cc',
        HeaderConsts::BCC => 'bcc',
        HeaderConsts::REPLY_TO => 'replyTo',
    ];

    /**
     * Converts a Magento Mail Message object to a Symfony\Component\Mime\Email object.
     *
     * This method prioritizes extracting the internal Symfony\Component\Mime\Email
     * instance from Magento's EmailMessageInterface if the getSymfonyMessage() method
     * is available. If the method is not available or fails, it falls back to
     * legacy message conversion using MIME parsing.
     *
     * @param MessageInterface $message The Magento mail message object.
     * @return SymfonyMimeEmail The converted Symfony Mailer Email object.
     * @throws InvalidArgumentException If the message cannot be converted or is malformed.
     * @throws RuntimeException If required dependencies are missing.
     */
    public function getSymfonyEmailMessage(MessageInterface $message)
    {
        // Check if the message is Magento's modern EmailMessageInterface.
        // This is the most common scenario in Magento 2.4.4+ and the most efficient path.
        if ($message instanceof EmailMessageInterface) {
            return $this->handleEmailMessageInterface($message);
        }

        // Handle older or custom MessageInterface
        return $this->convertLegacyMessage($message);
    }

    /**
     * Handle modern EmailMessageInterface
     *
     * @param EmailMessageInterface $message
     * @return SymfonyMimeEmail
     * @throws InvalidArgumentException
     */
    private function handleEmailMessageInterface(EmailMessageInterface $message)
    {
        // Check if getSymfonyMessage method exists (not guaranteed in all Magento versions)
        if (method_exists($message, 'getSymfonyMessage')) {
            try {
                $symfonyEmailMessage = $message->getSymfonyMessage();

                if ($symfonyEmailMessage instanceof SymfonyMimeEmail) {
                    return $symfonyEmailMessage;
                }
            } catch (Throwable $e) {
                // If getSymfonyMessage() throws an exception, fall back to legacy conversion
            }
        }

        // Fallback to legacy conversion if getSymfonyMessage is not available or failed
        return $this->convertLegacyMessage($message);
    }

    /**
     * Convert legacy message interface
     *
     * @param MessageInterface $message
     * @return SymfonyMimeEmail
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    private function convertLegacyMessage(MessageInterface $message)
    {
        $rawEmailString = $this->getRawEmailString($message);

        if (empty($rawEmailString)) {
            throw new InvalidArgumentException('Could not retrieve raw email string from MessageInterface.');
        }

        return $this->parseRawEmailString($rawEmailString);
    }

    /**
     * Parse raw email string using MIME parser
     *
     * @param string $rawEmailString
     * @return SymfonyMimeEmail
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    private function parseRawEmailString(string $rawEmailString)
    {
        $parser = $this->createMimeParser();

        if ($parser === null) {
            throw new RuntimeException(
                'Could not instantiate a suitable MIME parser. Ensure "zbateson/mail-mime-parser" library is installed and compatible.'
            );
        }

        try {
            /** @var ParsedMessage $parsed */
            $parsed = $parser->parse($rawEmailString, false);
            return $this->buildSymfonyEmailFromParsed($parsed);

        } catch (Throwable $e) {
            throw new InvalidArgumentException(
                sprintf('Error parsing email string with MailMimeParser: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Build Symfony email from parsed message
     *
     * @param ParsedMessage $parsed
     * @return SymfonyMimeEmail
     */
    private function buildSymfonyEmailFromParsed(ParsedMessage $parsed)
    {
        $email = new SymfonyMimeEmail();

        // Set addresses
        $this->setEmailAddresses($email, $parsed);

        // Set subject
        $subject = $parsed->getHeaderValue('Subject');
        $email->subject($subject ?? '');

        // Set body content
        $this->setEmailBody($email, $parsed);

        // Add attachments
        $this->addEmailAttachments($email, $parsed);

        return $email;
    }

    /**
     * Set email addresses from parsed message
     *
     * @param SymfonyMimeEmail $email
     * @param ParsedMessage $parsed
     */
    private function setEmailAddresses(SymfonyMimeEmail $email, ParsedMessage $parsed): void
    {
        foreach (self::HEADER_MAPPINGS as $header => $method) {
            $headerVal = $parsed->getHeaderValue($header);
            if (!empty($headerVal)) {
                $addresses = $this->convertHeaderAddresses($headerVal);
                if (!empty($addresses)) {
                    $email->$method(...$addresses);
                }
            }
        }
    }

    /**
     * Set email body from parsed message
     *
     * @param SymfonyMimeEmail $email
     * @param ParsedMessage $parsed
     */
    private function setEmailBody(SymfonyMimeEmail $email, ParsedMessage $parsed): void
    {
        $html = $parsed->getHtmlContent();
        $text = $parsed->getTextContent();

        if (!empty($html)) {
            $email->html($html);
        }

        if (!empty($text)) {
            $email->text($text);
        }

        // Fallback: if both text/html are missing, use raw content
        if (empty($html) && empty($text)) {
            $body = $parsed->getContent();
            if (!empty($body)) {
                $email->text($body);
            }
        }
    }

    /**
     * Add attachments from parsed message
     *
     * @param SymfonyMimeEmail $email
     * @param ParsedMessage $parsed
     */
    private function addEmailAttachments(SymfonyMimeEmail $email, ParsedMessage $parsed): void
    {
        $attachments = $parsed->getAllAttachmentParts();

        foreach ($attachments as $attachment) {
            try {
                $stream = $attachment->getContentStream();
                $filename = $attachment->getFilename() ?: 'attachment_' . uniqid();
                $mimeType = $attachment->getContentType() ?: 'application/octet-stream';

                if ($stream !== null) {
                    $email->attach($stream, $filename, $mimeType);
                }
            } catch (Throwable $e) {
                // Log attachment error but continue processing
                // Could add logging here if logger is available
                continue;
            }
        }
    }

    /**
     * Extract raw email string from different message types
     *
     * @param MessageInterface $message
     * @return string
     * @throws InvalidArgumentException
     */
    private function getRawEmailString(MessageInterface $message): string
    {
        // Try EmailMessageInterface methods first
        if ($message instanceof EmailMessageInterface) {
            $rawString = $this->tryExtractFromEmailMessage($message);
            if (!empty($rawString)) {
                return $rawString;
            }
        }

        // Try MailMessageInterface methods
        if ($message instanceof MailMessageInterface) {
            $rawString = $this->tryExtractFromMailMessage($message);
            if (!empty($rawString)) {
                return $rawString;
            }
        }

        // Try generic toString method
        if (method_exists($message, 'toString')) {
            $rawString = $message->toString();
            if (!empty($rawString)) {
                return $rawString;
            }
        }

        // Last resort - construct manually
        return $this->constructRawEmailString($message);
    }

    /**
     * Try to extract raw string from EmailMessageInterface
     *
     * @param EmailMessageInterface $message
     * @return string
     */
    private function tryExtractFromEmailMessage(EmailMessageInterface $message): string
    {
        $methods = ['toString', 'getRawMessage', '__toString'];

        // First try getSymfonyMessage if available
        if (method_exists($message, 'getSymfonyMessage')) {
            try {
                $symfonyMessage = $message->getSymfonyMessage();
                if ($symfonyMessage && method_exists($symfonyMessage, 'toString')) {
                    $result = $symfonyMessage->toString();
                    if (is_string($result) && !empty($result)) {
                        return $result;
                    }
                }
            } catch (Throwable $e) {
                // Continue to other methods
            }
        }

        // Try other methods
        foreach ($methods as $method) {
            if (method_exists($message, $method)) {
                try {
                    $result = $message->$method();
                    if (is_string($result) && !empty($result)) {
                        return $result;
                    }
                } catch (Throwable $e) {
                    // Continue to next method
                    continue;
                }
            }
        }

        return '';
    }

    /**
     * Try to extract raw string from MailMessageInterface
     *
     * @param MailMessageInterface $message
     * @return string
     */
    private function tryExtractFromMailMessage(MailMessageInterface $message): string
    {
        $methods = ['getRawMessage', 'toString', '__toString'];

        foreach ($methods as $method) {
            if (method_exists($message, $method)) {
                try {
                    $result = $message->$method();
                    if (is_string($result) && !empty($result)) {
                        return $result;
                    }
                } catch (Throwable $e) {
                    // Continue to next method
                    continue;
                }
            }
        }

        return '';
    }

    /**
     * Construct raw email string manually from MessageInterface
     *
     * @param MessageInterface $message
     * @return string
     */
    private function constructRawEmailString(MessageInterface $message): string
    {
        $headers = [];
        $body = '';

        // Extract headers safely
        $headers[] = $this->extractHeader($message, 'getFrom', 'From');
        $headers[] = $this->extractHeader($message, 'getTo', 'To');
        $headers[] = $this->extractHeader($message, 'getCc', 'Cc');
        $headers[] = $this->extractHeader($message, 'getBcc', 'Bcc');
        $headers[] = $this->extractHeader($message, 'getSubject', 'Subject');

        // Add standard headers
        $headers[] = 'Date: ' . date('r');
        $headers[] = 'Content-Type: ' . self::CONTENT_TYPE_TEXT . '; charset=' . self::DEFAULT_CHARSET;
        $headers[] = 'MIME-Version: 1.0';

        // Extract body
        if (method_exists($message, 'getBody')) {
            try {
                $body = (string) $message->getBody();
            } catch (Throwable $e) {
                $body = '';
            }
        }

        // Filter out empty headers
        $validHeaders = array_filter($headers, function($header) {
            return !empty($header) && strpos($header, ': ') !== false;
        });

        return implode("\r\n", $validHeaders) . "\r\n\r\n" . $body;
    }

    /**
     * Safely extract header from message
     *
     * @param MessageInterface $message
     * @param string $method
     * @param string $headerName
     * @return string
     */
    private function extractHeader(MessageInterface $message, string $method, string $headerName): string
    {
        if (!method_exists($message, $method)) {
            return '';
        }

        try {
            $value = $message->$method();

            if (is_array($value)) {
                $value = implode(', ', array_filter($value));
            }

            $value = (string) $value;
            return !empty($value) ? "{$headerName}: {$value}" : '';

        } catch (Throwable $e) {
            return '';
        }
    }

    /**
     * Dynamically instantiates the correct MIME parser based on available classes.
     *
     * @return MailMimeParser|null
     */
    private function createMimeParser(): ?MailMimeParser
    {
        if (class_exists(MailMimeParser::class)) {
            return new MailMimeParser();
        }

        return null;
    }

    /**
     * Convert header addresses string to Symfony Address objects
     *
     * @param string $rawHeader
     * @return Address[]
     */
    private function convertHeaderAddresses(string $rawHeader): array
    {
        $addresses = [];
        $parts = $this->parseAddressHeader($rawHeader);

        foreach ($parts as $part) {
            $address = $this->createAddressFromString($part);
            if ($address !== null) {
                $addresses[] = $address;
            }
        }

        return $addresses;
    }

    /**
     * Parse address header into parts
     *
     * @param string $rawHeader
     * @return string[]
     */
    private function parseAddressHeader(string $rawHeader): array
    {
        // Handle quoted names that might contain commas
        $parts = [];
        $current = '';
        $inQuotes = false;
        $inAngleBrackets = false;

        for ($i = 0; $i < strlen($rawHeader); $i++) {
            $char = $rawHeader[$i];

            if ($char === '"' && ($i === 0 || $rawHeader[$i-1] !== '\\')) {
                $inQuotes = !$inQuotes;
            } elseif ($char === '<' && !$inQuotes) {
                $inAngleBrackets = true;
            } elseif ($char === '>' && !$inQuotes) {
                $inAngleBrackets = false;
            } elseif ($char === ',' && !$inQuotes && !$inAngleBrackets) {
                $parts[] = trim($current);
                $current = '';
                continue;
            }

            $current .= $char;
        }

        if (!empty($current)) {
            $parts[] = trim($current);
        }

        return array_filter($parts);
    }

    /**
     * Create Address object from string
     *
     * @param string $addressString
     * @return Address|null
     */
    private function createAddressFromString(string $addressString): ?Address
    {
        $trimmed = trim($addressString);

        if (empty($trimmed)) {
            return null;
        }

        // Pattern: "Name" <email@domain.com> or Name <email@domain.com>
        if (preg_match('/"?([^"<]+?)"?\s*<([^>]+)>/', $trimmed, $matches)) {
            $name = trim($matches[1]);
            $email = trim($matches[2]);

            if (filter_var($email, FILTER_VALIDATE_EMAIL) && class_exists(Address::class)) {
                return new Address($email, $name);
            }
        }

        // Pattern: just email address
        if (filter_var($trimmed, FILTER_VALIDATE_EMAIL) && class_exists(Address::class)) {
            return new Address($trimmed);
        }

        return null;
    }
}
