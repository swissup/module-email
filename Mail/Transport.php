<?php
namespace Swissup\Email\Mail;

use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

use Swissup\Email\Api\Data\ServiceInterface;
use Swissup\Email\Model\Service;
use Swissup\Email\Model\ServiceFactory;
use Swissup\Email\Model\HistoryFactory;
use Swissup\Email\Mail\Transport\Factory as TransportFactory;

class Transport implements \Magento\Framework\Mail\TransportInterface
{
    const SERVICE_CONFIG = 'email/default/service';
    const LOG_CONFIG = 'email/default/log';

    /**
     * @var MessageInterface
     */
    protected $message;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ServiceFactory
     */
    protected $serviceFactory;

    /**
     * @var TransportFactory
     */
    protected $transportFactory;

    /**
     * Config options for sendmail parameters
     *
     * @var null
     */
    protected $parameters;

    /**
     *
     * @var HistoryFactory
     */
    protected $historyFactory;

    /**
     *
     * @var Service
     */
    protected $service;

    /**
     *
     * @param MessageInterface $message
     * @param ScopeConfigInterface $scopeConfig
     * @param ServiceFactory $serviceFactory
     * @param TransportFactory $transportFactory
     * @param HistoryFactory $historyFactory
     * @param null $parameters
     * @throws \InvalidArgumentException
     */
    public function __construct(
        /*MessageInterface*/ $message, //Magento\Framework\Mail\EmailMessage
        ScopeConfigInterface $scopeConfig,
        ServiceFactory $serviceFactory,
        TransportFactory $transportFactory,
        HistoryFactory $historyFactory,
        $parameters = null
    ) {
        // if (!$message instanceof MessageInterface) {
        //     throw new \InvalidArgumentException('The message should be an instance of \Magento\Framework\Mail\MessageInterface');
        // }
        $this->message = $message;
        $this->scopeConfig = $scopeConfig;
        $this->serviceFactory = $serviceFactory;
        $this->transportFactory = $transportFactory;
        $this->historyFactory = $historyFactory;
        $this->parameters = $parameters;
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

            $message = $this->message;
            $args = [
                'config'  => $service->getData(),
                'parameters' => $this->parameters
            ];
            $type = $service->getTransportNameByType();
            $transport = $this->transportFactory->create($type, $args);
            $transport->setMessage($message);
            $transport->sendMessage();

            $isLoggingEnabled = $this->scopeConfig->isSetFlag(self::LOG_CONFIG, ScopeInterface::SCOPE_STORE);
            if ($isLoggingEnabled) {
                $historyEntry = $this->historyFactory->create();
                $historyEntry->setServiceId($service->getId())
                    ->saveMessage($message);
            }
        } catch (\Exception $e) {
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
    public function setMessage(\Magento\Framework\Mail\MessageInterface $message)
    {
        $this->message = $message;
        return $this;
    }
}
