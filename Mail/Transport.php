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
use Swissup\Email\Model\History;
use Swissup\Email\Model\HistoryFactory;
use Swissup\Email\Model\Service;
use Swissup\Email\Model\ServiceFactory;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport as SymfonyTransport;
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
    private ?Mailer $symfonyMailer = null;

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

            $mailer->send($symfonyEmailMessage);
            $this->logMessage($this->message, $service);

        } catch (MailException $e) {
            // Re-throw MailException as-is
            throw $e;
        } catch (Throwable $e) {
            $this->logger->error('Email sending failed', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new MailException(
                new Phrase('Failed to send email: %1', [$e->getMessage()]),
                $e
            );
        }
    }

    /**
     * Get Symfony email message from Magento message
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
     * @return Mailer
     * @throws MailException
     */
    private function getSymfonyMailer(Service $service): Mailer
    {
        if ($this->symfonyMailer !== null) {
            return $this->symfonyMailer;
        }

        try {
            $dsnString = $this->buildDsnString($service);
            $symfonyTransport = SymfonyTransport::fromDsn($dsnString);
            $this->symfonyMailer = new Mailer($symfonyTransport);

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
