<?php

declare(strict_types=1);

namespace Swissup\Email\Mail\Message;

use InvalidArgumentException;
use RuntimeException;
use Throwable;
use Psr\Log\LoggerInterface;
use Magento\Framework\Mail\EmailMessageInterface;
use Magento\Framework\Mail\MailMessageInterface;
use Magento\Framework\Mail\MessageInterface;
use Symfony\Component\Mime\Email as SymfonyMimeEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Exception\InvalidArgumentException as MimeInvalidArgumentException;
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

    private const ADDRESS_HEADERS = ['from', 'to', 'cc', 'bcc', 'reply-to'];

    /**
     * @var LoggerInterface|null
     */
    private $logger;

    /**
     * @param LoggerInterface|null $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

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
    public function getSymfonyEmailMessage(MessageInterface $message): SymfonyMimeEmail
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
    private function handleEmailMessageInterface(EmailMessageInterface $message): SymfonyMimeEmail
    {
        // Check if getSymfonyMessage method exists (not guaranteed in all Magento versions)
        if (method_exists($message, 'getSymfonyMessage')) {
            try {
                $symfonyEmailMessage = $message->getSymfonyMessage();

                if ($symfonyEmailMessage instanceof SymfonyMimeEmail) {
                    return $symfonyEmailMessage;
                }
            } catch (Throwable $e) {
                $this->logWarning('Failed to extract Symfony message, falling back to legacy conversion', [
                    'error' => $e->getMessage(),
                    'message_class' => get_class($message)
                ]);
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
    private function convertLegacyMessage(MessageInterface $message): SymfonyMimeEmail
    {
        $rawEmailString = $this->getRawEmailString($message);

        if (empty($rawEmailString)) {
            $this->logError('Could not retrieve raw email string from MessageInterface', [
                'message_class' => get_class($message)
            ]);
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
    private function parseRawEmailString(string $rawEmailString): SymfonyMimeEmail
    {
        $parser = $this->createMimeParser();

        if ($parser === null) {
            $this->logCritical('MIME parser library not available', [
                'required_class' => MailMimeParser::class
            ]);
            throw new RuntimeException(
                'Could not instantiate a suitable MIME parser. Ensure "zbateson/mail-mime-parser" library is installed and compatible.'
            );
        }

        try {
            /** @var ParsedMessage $parsed */
            $parsed = $parser->parse($rawEmailString, false);
            return $this->buildSymfonyEmailFromParsed($parsed);

        } catch (Throwable $e) {
            $this->logError('Error parsing email string with MailMimeParser', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
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
    private function buildSymfonyEmailFromParsed(ParsedMessage $parsed): SymfonyMimeEmail
    {
        $email = new SymfonyMimeEmail();

        // Set addresses
        $this->setEmailAddresses($email, $parsed);

        // Set subject with proper UTF-8 handling
        $subject = $this->extractAndDecodeSubject($parsed);
        $email->subject($subject);

        // Set body content
        $this->setEmailBody($email, $parsed);

        // Add attachments
        $this->addEmailAttachments($email, $parsed);

        return $email;
    }

    /**
     * Extract and decode subject header with proper UTF-8 handling
     *
     * @param ParsedMessage $parsed
     * @return string
     */
    private function extractAndDecodeSubject(ParsedMessage $parsed): string
    {
        $subject = $parsed->getHeaderValue('Subject');

        if ($subject === null) {
            return '';
        }

        // Subject is already decoded by MailMimeParser
        // Ensure it's valid UTF-8
        if (!mb_check_encoding($subject, 'UTF-8')) {
            $subject = mb_convert_encoding($subject, 'UTF-8', 'UTF-8');
            $this->logWarning('Subject header contained invalid UTF-8, converted', [
                'subject_preview' => mb_substr($subject, 0, 50)
            ]);
        }

        return $subject;
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
                try {
                    $addresses = $this->convertHeaderAddresses($headerVal, $header);
                    if (!empty($addresses)) {
                        $email->$method(...$addresses);
                    }
                } catch (MimeInvalidArgumentException $e) {
                    // Log but continue - Symfony will validate addresses
                    $this->logWarning("Invalid address in {$header} header", [
                        'header' => $header,
                        'value' => $headerVal,
                        'error' => $e->getMessage()
                    ]);
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
            } else {
                $this->logWarning('Email has no body content (text, HTML, or raw)');
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

        if (empty($attachments)) {
            return;
        }

        $attachmentCount = 0;
        $failedCount = 0;

        foreach ($attachments as $attachment) {
            try {
                $stream = $attachment->getContentStream();
                $filename = $attachment->getFilename() ?: 'attachment_' . uniqid();
                $mimeType = $attachment->getContentType() ?: 'application/octet-stream';

                if ($stream !== null) {
                    $content = is_resource($stream) ? stream_get_contents($stream) : (string) $stream;
                    $email->attach($content, $filename, $mimeType);
                    $attachmentCount++;
                } else {
                    $failedCount++;
                    $this->logWarning('Attachment has no content stream', [
                        'filename' => $filename
                    ]);
                }
            } catch (Throwable $e) {
                $failedCount++;
                $this->logWarning('Failed to attach file', [
                    'filename' => $attachment->getFilename() ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }

        if ($failedCount > 0) {
            $this->logWarning("Processed attachments: {$attachmentCount} succeeded, {$failedCount} failed");
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
            try {
                $rawString = $message->toString();
                if (!empty($rawString)) {
                    return $rawString;
                }
            } catch (Throwable $e) {
                $this->logWarning('toString() method failed', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Last resort - construct manually
        $this->logWarning('Using manual email construction as last resort', [
            'message_class' => get_class($message)
        ]);

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
                $this->logDebug('getSymfonyMessage()->toString() failed', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Try other methods
        $methods = ['toString', 'getRawMessage', '__toString'];

        foreach ($methods as $method) {
            if (method_exists($message, $method)) {
                try {
                    $result = $message->$method();
                    if (is_string($result) && !empty($result)) {
                        return $result;
                    }
                } catch (Throwable $e) {
                    $this->logDebug("Method {$method}() failed", [
                        'error' => $e->getMessage()
                    ]);
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
                    $this->logDebug("Method {$method}() failed", [
                        'error' => $e->getMessage()
                    ]);
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

        // Extract headers safely with UTF-8 encoding
        $headers[] = $this->extractHeader($message, 'getFrom', 'From');
        $headers[] = $this->extractHeader($message, 'getTo', 'To');
        $headers[] = $this->extractHeader($message, 'getCc', 'Cc');
        $headers[] = $this->extractHeader($message, 'getBcc', 'Bcc');
        $headers[] = $this->extractHeader($message, 'getSubject', 'Subject', true);

        // Add standard headers
        $headers[] = 'Date: ' . date('r');
        $headers[] = 'Content-Type: ' . self::CONTENT_TYPE_TEXT . '; charset=' . self::DEFAULT_CHARSET;
        $headers[] = 'Content-Transfer-Encoding: 8bit';
        $headers[] = 'MIME-Version: 1.0';

        // Extract body
        if (method_exists($message, 'getBody')) {
            try {
                $bodyContent = $message->getBody();

                // Handle different body types
                if (is_object($bodyContent) && method_exists($bodyContent, '__toString')) {
                    $body = (string) $bodyContent;
                } elseif (is_string($bodyContent)) {
                    $body = $bodyContent;
                } else {
                    $body = '';
                }

                // Ensure UTF-8
                if (!empty($body) && !mb_check_encoding($body, 'UTF-8')) {
                    $body = mb_convert_encoding($body, 'UTF-8', 'UTF-8');
                }
            } catch (Throwable $e) {
                $this->logWarning('Failed to extract body', [
                    'error' => $e->getMessage()
                ]);
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
     * Safely extract header from message with optional MIME encoding for non-ASCII
     *
     * @param MessageInterface $message
     * @param string $method
     * @param string $headerName
     * @param bool $mimeEncodeIfNeeded Whether to MIME-encode non-ASCII values (for Subject)
     * @return string
     */
    private function extractHeader(
        MessageInterface $message,
        string $method,
        string $headerName,
        bool $mimeEncodeIfNeeded = false
    ): string {
        if (!method_exists($message, $method)) {
            return '';
        }

        try {
            $value = $message->$method();

            if (is_array($value)) {
                $value = implode(', ', array_filter($value));
            }

            $value = (string) $value;

            if (empty($value)) {
                return '';
            }

            // Sanitize line breaks (security)
            $value = str_replace(["\r", "\n"], '', $value);

            // Handle non-ASCII characters
            $headerNameLower = strtolower($headerName);

            if (!$this->isAscii($value)) {
                if ($mimeEncodeIfNeeded && $headerNameLower === 'subject') {
                    // MIME-encode subject to preserve special characters
                    $value = $this->mimeEncodeHeaderValue($value);
                } elseif (in_array($headerNameLower, self::ADDRESS_HEADERS)) {
                    // For address headers, only strip non-ASCII from email addresses,
                    // but preserve display names via MIME encoding
                    $value = $this->sanitizeAddressHeader($value);
                }
            }

            return "{$headerName}: {$value}";

        } catch (Throwable $e) {
            $this->logWarning("Failed to extract header {$headerName}", [
                'method' => $method,
                'error' => $e->getMessage()
            ]);
            return '';
        }
    }

    /**
     * Check if string contains only ASCII characters
     *
     * @param string $value
     * @return bool
     */
    private function isAscii(string $value): bool
    {
        return mb_check_encoding($value, 'ASCII');
    }

    /**
     * MIME-encode header value for non-ASCII characters
     *
     * @param string $value
     * @return string
     */
    private function mimeEncodeHeaderValue(string $value): string
    {
        // RFC 2047 MIME encoding for header values
        // Format: =?charset?encoding?encoded-text?=
        // Using Q-encoding (Quoted-Printable)

        return mb_encode_mimeheader($value, self::DEFAULT_CHARSET, 'Q');
    }

    /**
     * Sanitize address header while preserving display names
     *
     * @param string $value
     * @return string
     */
    private function sanitizeAddressHeader(string $value): string
    {
        // Pattern: "Display Name" <email@domain.com>
        if (preg_match('/"?([^"<]+?)"?\s*<([^>]+)>/', $value, $matches)) {
            $displayName = trim($matches[1]);
            $email = trim($matches[2]);

            // MIME-encode display name if it contains non-ASCII
            if (!$this->isAscii($displayName)) {
                $displayName = $this->mimeEncodeHeaderValue($displayName);
            }

            // Ensure email is ASCII-only (strip non-ASCII as fallback)
            $email = preg_replace('/[^\x20-\x7E]/', '', $email);

            return "\"{$displayName}\" <{$email}>";
        }

        // Just email address - strip non-ASCII
        return preg_replace('/[^\x20-\x7E]/', '', $value);
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
     * @param string $headerType For logging purposes
     * @return Address[]
     */
    private function convertHeaderAddresses(string $rawHeader, string $headerType = 'unknown'): array
    {
        $addresses = [];
        $parts = $this->parseAddressHeader($rawHeader);

        foreach ($parts as $part) {
            try {
                $address = $this->createAddressFromString($part);
                if ($address !== null) {
                    $addresses[] = $address;
                }
            } catch (MimeInvalidArgumentException $e) {
                $this->logWarning("Invalid address in {$headerType} header", [
                    'raw_address' => $part,
                    'error' => $e->getMessage()
                ]);
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
        $length = strlen($rawHeader);

        for ($i = 0; $i < $length; $i++) {
            $char = $rawHeader[$i];

            if ($char === '"' && ($i === 0 || $rawHeader[$i-1] !== '\\')) {
                $inQuotes = !$inQuotes;
                $current .= $char;
            } elseif ($char === '<' && !$inQuotes) {
                $inAngleBrackets = true;
                $current .= $char;
            } elseif ($char === '>' && !$inQuotes) {
                $inAngleBrackets = false;
                $current .= $char;
            } elseif ($char === ',' && !$inQuotes && !$inAngleBrackets) {
                if (!empty(trim($current))) {
                    $parts[] = trim($current);
                }
                $current = '';
            } else {
                $current .= $char;
            }
        }

        if (!empty(trim($current))) {
            $parts[] = trim($current);
        }

        return array_filter($parts);
    }

    /**
     * Create Address object from string with UTF-8 display name support
     *
     * @param string $addressString
     * @return Address|null
     * @throws MimeInvalidArgumentException
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

            // Validate email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->logWarning('Invalid email address format', [
                    'email' => $email,
                    'raw' => $addressString
                ]);
                return null;
            }

            if (!class_exists(Address::class)) {
                $this->logCritical('Symfony Address class not available');
                return null;
            }

            // Address class handles UTF-8 display names properly
            return new Address($email, $name);
        }

        // Pattern: just email address
        if (filter_var($trimmed, FILTER_VALIDATE_EMAIL)) {
            if (!class_exists(Address::class)) {
                $this->logCritical('Symfony Address class not available');
                return null;
            }
            return new Address($trimmed);
        }

        $this->logWarning('Could not parse address string', [
            'raw' => $addressString
        ]);

        return null;
    }

    // ========== Logging Helper Methods ==========

    /**
     * Log debug message
     *
     * @param string $message
     * @param array $context
     */
    private function logDebug(string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->debug('[EmailConvertor] ' . $message, $context);
        }
    }

    /**
     * Log warning message
     *
     * @param string $message
     * @param array $context
     */
    private function logWarning(string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->warning('[EmailConvertor] ' . $message, $context);
        }
    }

    /**
     * Log error message
     *
     * @param string $message
     * @param array $context
     */
    private function logError(string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->error('[EmailConvertor] ' . $message, $context);
        }
    }

    /**
     * Log critical message
     *
     * @param string $message
     * @param array $context
     */
    private function logCritical(string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->critical('[EmailConvertor] ' . $message, $context);
        }
    }
}
