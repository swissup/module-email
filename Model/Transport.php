<?php
namespace Swissup\Email\Model;

use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

use Swissup\Email\Api\Data\ServiceInterface;
use Swissup\Email\Model\ServiceFactory;
use Swissup\Email\Model\HistoryFactory;
use Swissup\Email\Model\Transport\Factory as TransportFactory;

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
     * @param MessageInterface $message
     * @param ScopeConfigInterface $scopeConfig
     * @param ServiceFactory $serviceFactory
     * @param TransportFactory $transportFactory
     * @param HistoryFactory $historyFactory
     * @param null $parameters
     * @throws \InvalidArgumentException
     */
    public function __construct(
        MessageInterface $message,
        ScopeConfigInterface $scopeConfig,
        ServiceFactory $serviceFactory,
        TransportFactory $transportFactory,
        HistoryFactory $historyFactory,
        $parameters = null
    ) {
        if (!$message instanceof \Zend_Mail) {
            throw new \InvalidArgumentException('The message should be an instance of \Zend_Mail');
        }
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
            $service = $this->serviceFactory->create();
            $id = (int) $this->scopeConfig->getValue(self::SERVICE_CONFIG, ScopeInterface::SCOPE_STORE);
            if ($id) {
                $service->load($id);
            }

            $message = $this->message;
            $args = [
                'message' => $message,
                'config'  => $service->getData(),
                'parameters' => $this->parameters
            ];
            $type = $service->getTransportNameByType();
            $transport = $this->transportFactory->create($type, $args);
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
     * @inheritdoc
     */
    public function getMessage()
    {
        return $this->message;
    }
}
