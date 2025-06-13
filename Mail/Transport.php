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
use Swissup\Email\Mail\Transport\Factory as TransportFactory;
use Swissup\Email\Model\History;
use Swissup\Email\Model\HistoryFactory;
use Swissup\Email\Model\Service;
use Swissup\Email\Model\ServiceFactory;

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
    private TransportFactory $transportFactory;
    private HistoryFactory $historyFactory;
    private ?array $parameters;
    private LoggerInterface $logger;
    private ?Service $service = null;

    public function __construct(
        MessageInterface $message,
        Convertor $convertor,
        ScopeConfigInterface $scopeConfig,
        ServiceFactory $serviceFactory,
        TransportFactory $transportFactory,
        HistoryFactory $historyFactory,
        $parameters = null,
        ?LoggerInterface $logger = null
    ) {
        $this->message = $message;
        $this->convertor = $convertor;
        $this->scopeConfig = $scopeConfig;
        $this->serviceFactory = $serviceFactory;
        $this->transportFactory = $transportFactory;
        $this->historyFactory = $historyFactory;
        $this->parameters = $parameters;
        $this->logger = $logger ?? ObjectManager::getInstance()->get(LoggerInterface::class);
    }

    /**
     * @throws MailException
     */
    public function sendMessage(): void
    {
        try {
            $service = $this->getService();
            $message = $this->convertor->fromMessage($this->message);

            $transportConfig = $this->buildTransportConfig($service);
            $transport = $this->createTransport($service, $transportConfig);

            $transport->setMessage($message);
            $transport->sendMessage();

            $this->logMessageIfEnabled($message, $service);
        } catch (\Exception $e) {
            $this->logger->error($e);
            throw new MailException(new Phrase($e->getMessage()), $e);
        }
    }

    private function buildTransportConfig(Service $service): array
    {
        $config = [
            'config' => $service->getData(),
            'parameters' => $this->parameters
        ];

        $sendingHost = (string) $this->scopeConfig->getValue(self::CONFIG['EHLO']);
        if (!empty($sendingHost)) {
            $config['config']['sending_host'] = $sendingHost;
        }

        return $config;
    }

    private function createTransport(Service $service, array $config)
    {
        $type = $service->getTransportNameByType();
        return $this->transportFactory->create($type, $config);
    }

    private function logMessageIfEnabled($message, Service $service): void
    {
        $isLoggingEnabled = $this->scopeConfig->isSetFlag(
            self::CONFIG['LOG'],
            ScopeInterface::SCOPE_STORE
        );

        if ($isLoggingEnabled) {
            /** @var History $historyEntry */
            $historyEntry = $this->historyFactory->create();
            $historyEntry->setServiceId($service->getId());
            $historyEntry->saveMessage($message);
        }
    }

    public function getService(): Service
    {
        if ($this->service === null) {
            $service = $this->serviceFactory->create();
            $id = (int) $this->scopeConfig->getValue(
                self::CONFIG['SERVICE'],
                ScopeInterface::SCOPE_STORE
            );

            if ($id) {
                $service->load($id);
            }

            $this->service = $service;
        }

        return $this->service;
    }

    public function setService(Service $service): self
    {
        $this->service = $service;
        return $this;
    }

    public function getMessage(): MessageInterface
    {
        return $this->message;
    }

    public function setMessage(MessageInterface $message): self
    {
        $this->message = $message;
        return $this;
    }
}
