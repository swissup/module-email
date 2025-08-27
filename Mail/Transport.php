<?php

declare(strict_types=1);

namespace Swissup\Email\Mail;

use InvalidArgumentException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\TransportInterface;
use Magento\Framework\Phrase;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Swissup\Email\Mail\Message\Convertor;
use Swissup\Email\Mail\Transport\GmailOAuth2;
use Swissup\Email\Model\History;
use Swissup\Email\Model\HistoryFactory;
use Swissup\Email\Model\Service;
use Swissup\Email\Model\ServiceFactory;
use Symfony\Component\Mailer\Transport as SymfonyTransport;
use Symfony\Component\Mailer\Transport\Dsn;
use Throwable;

class Transport implements TransportInterface
{
    private const CONFIG = [
        'SERVICE' => 'email/default/service',
        'LOG' => 'email/default/log',
        'EHLO' => 'email/default/sending_host'
    ];

    private MessageInterface $message;
    private Convertor $convertor;
    private ScopeConfigInterface $scopeConfig;
    private ServiceFactory $serviceFactory;
    private HistoryFactory $historyFactory;
    private ?array $parameters;
    private LoggerInterface $logger;
    private ?Service $service = null;
    /** @var mixed */
    private $symfonyMailer = null;

    public function __construct(
        MessageInterface $message,
        Convertor $convertor,
        ScopeConfigInterface $scopeConfig,
        ServiceFactory $serviceFactory,
        HistoryFactory $historyFactory,
        $parameters = null,
        ?LoggerInterface $logger = null
    ) {
        $this->message = $message;
        $this->convertor = $convertor;
        $this->scopeConfig = $scopeConfig;
        $this->serviceFactory = $serviceFactory;
        $this->historyFactory = $historyFactory;
        $this->parameters = $parameters;
        $this->logger = $logger ?? ObjectManager::getInstance()->get(LoggerInterface::class);
    }

    /**
     * Send email message
     *
     * @throws MailException
     */
    public function sendMessage(): void
    {
        try {
            $service = $this->getService();
            $symfonyEmailMessage = $this->getSymfonyEmailMessage();
            $mailer = $this->getSymfonyMailer($service);

            if (!$mailer || !is_object($mailer)) {
                throw new MailException(new Phrase('Mailer instance is not available or invalid.'));
            }

            // Use reflection to call send method safely
            if (method_exists($mailer, 'send')) {
                $mailer->send($symfonyEmailMessage);
            } else {
                throw new MailException(new Phrase('Mailer send method not available'));
            }

            $this->logMessage($this->message, $service);

        } catch (MailException $e) {
            // Re-throw MailException as-is
            throw $e;
        } catch (Throwable $e) {
            $this->logger->error('Email sending failed', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $cause = $e instanceof Exception ? $e : new \Exception($e->getMessage(), $e->getCode(), $e);

            throw new MailException(
                new Phrase('Failed to send email: %1', [$e->getMessage()]),
                $cause
            );
        }
    }

    /**
     * Get Symfony email message from Magento message
     *
     * @return mixed
     */
    private function getSymfonyEmailMessage()
    {
        if (method_exists($this->message, 'getSymfonyMessage')) {
            return $this->message->getSymfonyMessage();
        }

        return $this->convertor->getSymfonyEmailMessage($this->message);
    }

    /**
     * Gets or creates the Symfony Mailer instance and its underlying transport.
     * Caches the Mailer instance for subsequent calls.
     *
     * @param Service $service
     * @return mixed
     * @throws MailException
     */
    private function getSymfonyMailer(Service $service)
    {
        if ($this->symfonyMailer !== null) {
            return $this->symfonyMailer;
        }

        try {
            $dsnString = $this->buildDsnString($service);

            // Check if Symfony classes exist
            if (!class_exists(SymfonyTransport::class)) {
                throw new MailException(new Phrase('Symfony Mailer Transport class not found. Please install symfony/mailer package.'));
            }

            if (!class_exists(\Symfony\Component\Mailer\Mailer::class)) {
                throw new MailException(new Phrase('Symfony Mailer class not found. Please install symfony/mailer package.'));
            }

            // Створюємо транспорт з підтримкою кастомних транспортів
            $symfonyTransport = $this->createTransportFromDsn($dsnString);

            // Create mailer
            $mailerClass = \Symfony\Component\Mailer\Mailer::class;
            $this->symfonyMailer = new $mailerClass($symfonyTransport);

        } catch (InvalidArgumentException $e) {
            throw new MailException(
                new Phrase('Invalid email service configuration: %1', [$e->getMessage()]),
                $e
            );
        } catch (Throwable $e) {
            throw new MailException(
                new Phrase('Could not create email transport: %1', [$e->getMessage()]),
                $e
            );
        }

        return $this->symfonyMailer;
    }

    /**
     * Creates a transport with support for custom schemes
     *
     * @param string $dsnString
     * @return mixed
     * @throws InvalidArgumentException
     */
    private function createTransportFromDsn(string $dsnString)
    {
        $this->logger->info('Creating transport from DSN', ['scheme' => parse_url($dsnString, PHP_URL_SCHEME)]);

        // Check if this is our custom Gmail OAuth2 DSN
        if (str_starts_with($dsnString, 'gmail+oauth2://')) {
            // Create Symfony DSN object
            $dsn = Dsn::fromString($dsnString);
            return GmailOAuth2::fromDsn($dsn);
        }

        // For all other DSNs use the standard Symfony Transport
        if (method_exists(SymfonyTransport::class, 'fromDsn')) {
            return SymfonyTransport::fromDsn($dsnString);
        }

        throw new InvalidArgumentException('Transport fromDsn method not available');
    }

    /**
     * Build DSN string with optional sending host
     *
     * @param Service $service
     * @return string
     */
    private function buildDsnString(Service $service): string
    {
        $dsnString = $service->getDsn();
        $sendingHost = trim((string) $this->scopeConfig->getValue(self::CONFIG['EHLO']));

        if (empty($sendingHost)) {
            return $dsnString;
        }

        $separator = str_contains($dsnString, '?') ? '&' : '?';
        return $dsnString . $separator . 'local_domain=' . urlencode($sendingHost);
    }

    /**
     * Log message if logging is enabled
     *
     * @param MessageInterface $message
     * @param Service $service
     */
    private function logMessage(MessageInterface $message, Service $service): void
    {
        $isLoggingEnabled = $this->scopeConfig->isSetFlag(
            self::CONFIG['LOG'],
            ScopeInterface::SCOPE_STORE
        );

        if (!$isLoggingEnabled) {
            return;
        }

        try {
            /** @var History $historyEntry */
            $historyEntry = $this->historyFactory->create();
            $historyEntry->setServiceId($service->getId());
            $historyEntry->saveMessage($message);
        } catch (Throwable $e) {
            // Log the error but don't fail the email sending process
            $this->logger->warning('Failed to log email message', [
                'exception' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get email service
     *
     * @return Service
     * @throws MailException
     */
    public function getService(): Service
    {
        if ($this->service !== null) {
            return $this->service;
        }

        $service = $this->serviceFactory->create();
        $serviceId = (int) $this->scopeConfig->getValue(
            self::CONFIG['SERVICE'],
            ScopeInterface::SCOPE_STORE
        );

        if ($serviceId > 0) {
            $service->load($serviceId);

            // Validate that service was loaded successfully
            if (!$service->getId()) {
                throw new MailException(
                    new Phrase('Email service with ID %1 not found', [$serviceId])
                );
            }
        }

        $this->service = $service;
        return $this->service;
    }

    /**
     * Set email service
     *
     * @param Service $service
     * @return self
     */
    public function setService(Service $service): self
    {
        $this->service = $service;
        // Reset mailer when service changes
        $this->symfonyMailer = null;
        return $this;
    }

    /**
     * Get message
     *
     * @return MessageInterface
     */
    public function getMessage(): MessageInterface
    {
        return $this->message;
    }

    /**
     * Set message
     *
     * @param MessageInterface $message
     * @return self
     */
    public function setMessage(MessageInterface $message): self
    {
        $this->message = $message;
        return $this;
    }
}
