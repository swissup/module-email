<?php
namespace Swissup\Email\Model;

use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

use Swissup\Email\Api\Data\ServiceInterface;
use Swissup\Email\Model\ServiceFactory;
use Swissup\Email\Model\Transport\Factory as TransportFactory;

class Transport implements \Magento\Framework\Mail\TransportInterface
{
    const SERVICE_CONFIG = 'email/default/service';

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
     * @param MessageInterface $message
     * @param ScopeConfigInterface $scopeConfig
     * @param ServiceFactory $serviceFactory
     * @param TransportFactory $transportFactory
     * @param null $parameters
     * @throws \InvalidArgumentException
     */
    public function __construct(
        MessageInterface $message,
        ScopeConfigInterface $scopeConfig,
        ServiceFactory $serviceFactory,
        TransportFactory $transportFactory,
        $parameters = null
    ) {
        if (!$message instanceof \Zend_Mail) {
            throw new \InvalidArgumentException('The message should be an instance of \Zend_Mail');
        }
        $this->message = $message;
        $this->scopeConfig = $scopeConfig;
        $this->serviceFactory = $serviceFactory;
        $this->transportFactory = $transportFactory;
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

            $args = [
                'message' => $this->message,
                'config'  => $service->getData(),
                'parameters' => $this->parameters
            ];
            $type = $service->getTransportNameByType();

            $this->transportFactory
                ->create($type, $args)
                ->sendMessage()
            ;
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
