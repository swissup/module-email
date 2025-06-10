<?php
namespace Swissup\Email\Mail;

use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

use Swissup\Email\Api\Data\ServiceInterface;
use Swissup\Email\Model\Service;
use Swissup\Email\Model\ServiceFactory;
use Swissup\Email\Model\HistoryFactory;
use Swissup\Email\Mail\Transport\Factory as TransportFactory;

use Psr\Log\LoggerInterface;

class Transport implements \Magento\Framework\Mail\TransportInterface
{
    const SERVICE_CONFIG = 'email/default/service';
    const LOG_CONFIG = 'email/default/log';
    const EHLO_CONFIG = 'email/default/sending_host';

    /**
     * @var MessageInterface
     */
    protected $message;

    /**
     * @var \Swissup\Email\Mail\Message\Convertor
     */
    protected $convertor;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ServiceFactory
     */
    protected $serviceFactory;

    /**
     *
     * @var Service
     */
    protected $service;

    /**
     * @var TransportFactory
     */
    protected $transportFactory;

    /**
     *
     * @var HistoryFactory
     */
    protected $historyFactory;

    /**
     * Config options for sendmail parameters
     *
     * @var null
     */
    protected $parameters;

    /**
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     *
     * @param MessageInterface $message
     * @param \Swissup\Email\Mail\Message\Convertor $convertor
     * @param ScopeConfigInterface $scopeConfig
     * @param ServiceFactory $serviceFactory
     * @param TransportFactory $transportFactory
     * @param HistoryFactory $historyFactory
     * @param null $parameters
     * @param LoggerInterface|null $logger
     * @throws \InvalidArgumentException
     */
    public function __construct(
        /*MessageInterface*/ $message, //Magento\Framework\Mail\EmailMessage
        \Swissup\Email\Mail\Message\Convertor $convertor,
        ScopeConfigInterface $scopeConfig,
        ServiceFactory $serviceFactory,
        TransportFactory $transportFactory,
        HistoryFactory $historyFactory,
        $parameters = null,
        LoggerInterface $logger = null
    ) {
        // if (!$message instanceof MessageInterface) {
        //     throw new \InvalidArgumentException(
        //         'The message should be an instance of \Magento\Framework\Mail\MessageInterface'
        //     );
        // }
        $this->message = $message;
        $this->convertor = $convertor;
        $this->scopeConfig = $scopeConfig;
        $this->serviceFactory = $serviceFactory;
        $this->transportFactory = $transportFactory;
        $this->historyFactory = $historyFactory;
        $this->parameters = $parameters;
        $this->logger = $logger ?: ObjectManager::getInstance()->get(LoggerInterface::class);
    }

    /**
     * Send a mail using this transport
     *
     * @return void
     * @throws \Magento\Framework\Exception\MailException
     */
    public function sendMessage()
    {
        try {
            $service = $this->getService();

            $message = $this->convertor->fromMessage($this->message);
            $args = [
                'config'  => $service->getData(),
                'parameters' => $this->parameters
                // 'convertor' => $this->convertor
            ];
            $sendingHost = (string) $this->scopeConfig->getValue(self::EHLO_CONFIG);
            if (!empty($sendingHost)) {
                $args['config']['sending_host'] = $sendingHost;
            }
            $type = $service->getTransportNameByType();
            $transport = $this->transportFactory->create($type, $args);
            $transport->setMessage($message);
            $transport->sendMessage();

            $isLoggingEnabled = $this->scopeConfig->isSetFlag(self::LOG_CONFIG, ScopeInterface::SCOPE_STORE);
            if ($isLoggingEnabled) {
                /** @var \Swissup\Email\Model\History $historyEntry */
                $historyEntry = $this->historyFactory->create();
                $historyEntry->setServiceId($service->getId());
                $historyEntry->saveMessage($message);
            }
        } catch (\Exception $e) {
//            throw $e;
            $this->logger->error($e);
            $phrase = new \Magento\Framework\Phrase($e->getMessage());
            throw new \Magento\Framework\Exception\MailException($phrase, $e);
        }
    }

    /**
     *
     * @return Service
     */
    public function getService()
    {
        if ($this->service === null) {
            $service = $this->serviceFactory->create();
            $id = (int) $this->scopeConfig->getValue(self::SERVICE_CONFIG, ScopeInterface::SCOPE_STORE);
            if ($id) {
                $service->load($id);
            }

            $this->service = $service;
        }

        return $this->service;
    }

    /**
     *
     * @param Service $service
     */
    public function setService(Service $service)
    {
        $this->service = $service;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     *
     * @param  \Magento\Framework\Mail\MessageInterface $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }
}
