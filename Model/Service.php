<?php
namespace Swissup\Email\Model;

use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

use Swissup\Email\Api\Data\ServiceInterface;
use Magento\Framework\DataObject\IdentityInterface;

/**
 * Class Service implements service interface
 */
class Service extends \Magento\Framework\Model\AbstractModel implements ServiceInterface, IdentityInterface
{
    /**
     * cache tag
     */
    const CACHE_TAG = 'email_service';

    /**
     * @var string
     */
    protected $_cacheTag = 'email_service';

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'email_service';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Swissup\Email\Model\ResourceModel\Service::class);
    }

    /**
     * Return unique ID(s) for each object in system
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * Get id
     *
     * return int
     */
    public function getId()
    {
        return $this->getData(self::ID);
    }

    /**
     * Get name
     *
     * return string
     */
    public function getName()
    {
        return $this->getData(self::NAME);
    }

    /**
     * Get status
     *
     * return int
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * Get type
     *
     * return int
     */
    public function getType()
    {
        return (int) $this->getData(self::TYPE);
    }

    /**
     * Get host
     *
     * return string
     */
    public function getHost()
    {
        return $this->getData(self::HOST);
    }

    /**
     * Get user
     *
     * return string
     */
    public function getUser()
    {
        return $this->getData(self::USER);
    }

    /**
     * Get email
     *
     * return string
     */
    public function getEmail()
    {
        return $this->getData(self::EMAIL);
    }

    /**
     * Get password
     *
     * return string
     */
    public function getPassword()
    {
        return $this->getData(self::PASSWORD);
    }

    /**
     * Get port
     *
     * return int
     */
    public function getPort()
    {
        return $this->getData(self::PORT);
    }

    /**
     * Get secure
     *
     * return int
     */
    public function getSecure()
    {
        return $this->getData(self::SECURE);
    }

    /**
     * Get auth
     *
     * return string
     */
    public function getAuth()
    {
        return $this->getData(self::AUTH);
    }

    /**
     * @return int
     */
    public function getTokenId()
    {
        return $this->getData(self::TOKEN_ID);
    }

    /**
     * Get key
     *
     * return string
     */
    public function getKey()
    {
        return $this->getData(self::KEY);
    }

    /**
     * Get remove
     *
     * return string
     */
    public function getRemove()
    {
        return $this->getData(self::REMOVE);
    }

    /**
     * Set id
     *
     * @param int $id
     * return \Swissup\Email\Api\Data\ServiceInterface
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * Set name
     *
     * @param string $name
     * return \Swissup\Email\Api\Data\ServiceInterface
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * Set status
     *
     * @param int $status
     * return \Swissup\Email\Api\Data\ServiceInterface
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * Set type
     *
     * @param int $type
     * return \Swissup\Email\Api\Data\ServiceInterface
     */
    public function setType($type)
    {
        return $this->setData(self::TYPE, $type);
    }

    /**
     * Set host
     *
     * @param string $host
     * return \Swissup\Email\Api\Data\ServiceInterface
     */
    public function setHost($host)
    {
        return $this->setData(self::HOST, $host);
    }

    /**
     * Set user
     *
     * @param string $user
     * return \Swissup\Email\Api\Data\ServiceInterface
     */
    public function setUser($user)
    {
        return $this->setData(self::USER, $user);
    }

    /**
     * Set email
     *
     * @param string $email
     * return \Swissup\Email\Api\Data\ServiceInterface
     */
    public function setEmail($email)
    {
        return $this->setData(self::EMAIL, $email);
    }

    /**
     * Set password
     *
     * @param string $password
     * return \Swissup\Email\Api\Data\ServiceInterface
     */
    public function setPassword($password)
    {
        return $this->setData(self::PASSWORD, $password);
    }

    /**
     * Set port
     *
     * @param int $port
     * return \Swissup\Email\Api\Data\ServiceInterface
     */
    public function setPort($port)
    {
        return $this->setData(self::PORT, $port);
    }

    /**
     * Set secure
     *
     * @param int $secure
     * return \Swissup\Email\Api\Data\ServiceInterface
     */
    public function setSecure($secure)
    {
        return $this->setData(self::SECURE, $secure);
    }

    /**
     * Set auth
     *
     * @param string $auth
     * return \Swissup\Email\Api\Data\ServiceInterface
     */
    public function setAuth($auth)
    {
        return $this->setData(self::AUTH, $auth);
    }

    public function setTokenId($tokenId)
    {
        return $this->setData(self::TOKEN_ID, $tokenId);
    }

    /**
     * Set key
     *
     * @param string $key
     * return \Swissup\Email\Api\Data\ServiceInterface
     */
    public function setKey($key)
    {
        return $this->setData(self::KEY, $key);
    }

    /**
     * Set remove
     *
     * @param string $remove
     * return \Swissup\Email\Api\Data\ServiceInterface
     */
    public function setRemove($remove)
    {
        return $this->setData(self::REMOVE, $remove);
    }

    /**
     *
     * @return array
     */
    public function getStatuses()
    {
        return [
            self::ENABLED  => __('Enabled'),
            self::DISABLED => __('Disabled')
        ];
    }

    /**
     *
     * @return array
     */
    public function getPreDefinedSmtpProviderSettings()
    {
        return [
          [
            'name' => 'Mailgun',
            'host' => 'smtp.mailgun.org',
            'auth' => 'login',
            'port' => 587,
            'secure' => self::SECURE_TLS,
          ], [
            'name' => 'Mandrill',
            'host' => 'smtp.mandrillapp.com',
            'auth' => 'login',
            'port' => 587,
            'secure' => self::SECURE_TLS,
          ], [
            'name' => 'Sendinblue',
            'host' => 'smtp-relay.sendinblue.com',
            'auth' => 'login',
            'port' => 587,
            'secure' => self::SECURE_TLS,
          ], [
            'name' => 'Sendgrid',
            'host' => 'smtp.sendgrid.net',
            'auth' => 'login',
            'port' => 587,
            'secure' => self::SECURE_TLS,
          ], [
            'name' => 'Elastic Email',
            'host' => 'smtp.elasticemail.com',
            'auth' => 'login',
            'port' => 2525,
            'secure' => self::SECURE_NONE,
          ], [
            'name' => 'SparkPost',
            'host' => 'smtp.sparkpostmail.com',
            'auth' => 'login',
            'port' => 587,
            'secure' => self::SECURE_TLS,
          ], [
            'name' => 'Mailjet',
            'host' => 'in-v3.mailjet.com',
            'auth' => 'login',
            'port' => 587,
            'secure' => self::SECURE_TLS,
          ], [
            'name' => 'Postmark',
            'host' => 'smtp.postmarkapp.com',
            'auth' => 'login',
            'port' => 587,
            'secure' => self::SECURE_TLS,
          ], [
            'name' => 'AOL Mail',
            'host' => 'smtp.aol.com',
            'auth' => 'login',
            'port' => 587,
            'secure' => self::SECURE_NONE,
          ], [
            'name' => 'Comcast',
            'host' => 'smtp.comcast.net',
            'auth' => 'login',
            'port' => 587,
            'secure' => self::SECURE_NONE,
          ], [
            'name' => 'GMX',
            'host' => 'mail.gmx.net',
            'auth' => 'login',
            'port' => 587,
            'secure' => self::SECURE_TLS,
          ], [
            'name' => 'Gmail',
            'host' => 'smtp.gmail.com',
            'auth' => 'login',
            'port' => 465,
            'secure' => self::SECURE_SSL,
          ], [
            'name' => 'Gmail (OAuth 2)',
            'host' => 'smtp.gmail.com',
            'auth' => 'xoauth2',
            'port' => 587,
            'secure' => self::SECURE_SSL,
          ], [
            'name' => 'Hotmail',
            'host' => 'smtp-mail.outlook.com',
            'auth' => 'login',
            'port' => 587,
            'secure' => self::SECURE_TLS,
          ], [
            'name' => 'Mail.com',
            'host' => 'smtp.mail.com',
            'auth' => 'login',
            'port' => 587,
            'secure' => self::SECURE_NONE,
          ], [
            'name' => 'O2 Mail',
            'host' => 'smtp.o2.ie',
            'auth' => 'login',
            'port' => 25,
            'secure' => self::SECURE_NONE,
          ], [
            'name' => 'Office365',
            'host' => 'smtp.office365.com',
            'auth' => 'login',
            'port' => 587,
            'secure' => self::SECURE_NONE,
          ], [
            'name' => 'Orange',
            'host' => 'smtp.orange.net',
            'auth' => 'login',
            'port' => 25,
            'secure' => self::SECURE_NONE,
          ], [
            'name' => 'Outlook',
            'host' => 'smtp-mail.outlook.com',
            'auth' => 'login',
            'port' => 587,
            'secure' => self::SECURE_TLS,
          ], [
            'name' => 'Yahoo Mail',
            'host' => 'smtp.mail.yahoo.com',
            'auth' => 'login',
            'port' => 465,
            'secure' => self::SECURE_SSL,
          ], [
            'name' => 'Yahoo Mail Plus',
            'host' => 'plus.smtp.mail.yahoo.com',
            'auth' => 'login',
            'port' => 465,
            'secure' => self::SECURE_SSL,
          ], [
            'name' => 'Yahoo AU/NZ',
            'host' => 'smtp.mail.yahoo.com.au',
            'auth' => 'login',
            'port' => 465,
            'secure' => self::SECURE_SSL,
          ], [
            'name' => 'AT&T',
            'host' => 'smtp.att.yahoo.com',
            'auth' => 'login',
            'port' => 465,
            'secure' => self::SECURE_SSL,
          ], [
            'name' => 'NTL @ntlworld.com',
            'host' => 'smtp.ntlworld.com',
            'auth' => 'login',
            'port' => 465,
            'secure' => self::SECURE_SSL,
          ], [
            'name' => 'BT Connect',
            'host' => 'pop3.btconnect.com',
            'auth' => 'login',
            'port' => 25,
            'secure' => self::SECURE_NONE,
          ], [
            'name' => 'Zoho Mail',
            'host' => 'smtp.zoho.com',
            'auth' => 'login',
            'port' => 465,
            'secure' => self::SECURE_SSL,
          ], [
            'name' => 'Verizon',
            'host' => 'outgoing.verizon.net',
            'auth' => 'login',
            'port' => 465,
            'secure' => self::SECURE_SSL,
          ], [
            'name' => 'BT Openworld',
            'host' => 'mail.btopenworld.com',
            'auth' => 'login',
            'port' => 25,
            'secure' => self::SECURE_NONE,
          ], [
            'name' => 'O2 Online Deutschland',
            'host' => 'mail.o2online.de',
            'auth' => 'login',
            'port' => 25,
            'secure' => self::SECURE_NONE,
          ]
        ];
    }

    /**
     *
     * @return array
     */
    public function getTypes()
    {
        return [
            self::TYPE_GMAIL       => __('Gmail'),
            self::TYPE_GMAILOAUTH2 => __('Gmail OAuth 2'),
            self::TYPE_SMTP        => __('SMTP'),
            self::TYPE_SES         => __('Amazon SES'),
            self::TYPE_MANDRILL    => __('Mandrill'),
            self::TYPE_SENDMAIL    => __('Sendmail'),
        ];
    }

    /**
     *
     * @param  int $type
     * @return string
     */
    public function getTransportNameByType($type = null)
    {
        if (null == $type) {
            $type = $this->getData(self::TYPE);
        }
        $classes = [
            self::TYPE_GMAIL    => 'Gmail',
            self::TYPE_GMAILOAUTH2 => 'GmailOAuth2',
            self::TYPE_SMTP     => 'Smtp',
            self::TYPE_SES      => 'Ses',
            self::TYPE_MANDRILL => 'Mandrill',
            self::TYPE_SENDMAIL => 'Sendmail',
        ];

        return isset($classes[$type]) ? $classes[$type] : 'Sendmail';
    }

    /**
     *
     * @return array
     */
    public function getSecures()
    {
        return [
            self::SECURE_NONE => __('None'),
            self::SECURE_SSL  => __('SSL'),
            self::SECURE_TLS  => __('TLS')
        ];
    }

    /**
     *
     * @return array
     */
    public function getAuthTypes()
    {
        return [
            self::AUTH_TYPE_NONE   => __('None'),
            self::AUTH_TYPE_LOGIN   => __('Login'),
            self::AUTH_TYPE_PLAIN   => __('Plain'),
            self::AUTH_TYPE_CRAMMD5 => __('Crammd5'),
            self::AUTH_TYPE_XOAUTH2 => __('OAuth 2.0'),
        ];
    }
}
