<?php
namespace Swissup\Email\Model;

use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Swissup\Email\Model\Service\Factory;

class Transport implements \Magento\Framework\Mail\TransportInterface //extends \Zend_Mail_Transport_Smtp
{
    const SMTP_SERVICE_CONFIG = 'system/smtp/service';

    /**
     * @var MessageInterface
     */
    protected $message;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Factory
     */
    protected $serviceFactory;

    /**
     *
     * @param MessageInterface $message
     * @param ScopeConfigInterface $scopeConfig
     * @param Factory $serviceFactory
     * @throws \InvalidArgumentException
     */
    public function __construct(
        MessageInterface $message,
        ScopeConfigInterface $scopeConfig,
        Factory $serviceFactory
    ) {
        if (!$message instanceof \Zend_Mail) {
            throw new \InvalidArgumentException('The message should be an instance of \Zend_Mail');
        }
        $this->scopeConfig = $scopeConfig;
        // $scope = ScopeInterface::SCOPE_STORE;
        // $host = $scopeConfig->getValue(self::SMTP_HOST_CONFIG, $scope);
        // $config = array(
        //    'auth'     => $scopeConfig->getValue(self::SMTP_AUTH_CONFIG, $scope),
        //    'ssl'      => $scopeConfig->getValue(self::SMTP_SSL_CONFIG, $scope),
        //    'username' => $scopeConfig->getValue(self::SMTP_USER_CONFIG, $scope),
        //    'password' => $scopeConfig->getValue(self::SMTP_PASS_CONFIG, $scope)
        // );

        // parent::__construct($host, $config);
        $this->message = $message;
        $this->serviceFactory = $serviceFactory;
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
            // parent::send($this->message);
        } catch (\Exception $e) {
            $phrase = new \Magento\Framework\Phrase($e->getMessage());
            throw new \Magento\Framework\Exception\MailException($phrase, $e);
        }
    }
}
