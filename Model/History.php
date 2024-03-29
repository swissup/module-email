<?php
namespace Swissup\Email\Model;

use Swissup\Email\Api\Data\HistoryInterface;
use Magento\Framework\DataObject\IdentityInterface;

/* Swissup/Email/Model/History.php */

/**
 * Class History implements history interface
 */
class History extends \Magento\Framework\Model\AbstractModel implements HistoryInterface, IdentityInterface
{
    /**
     * cache tag
     */
    const CACHE_TAG = 'email_history';

    /**
     * @var string
     */
    protected $_cacheTag = 'email_history';

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'email_history';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Swissup\Email\Model\ResourceModel\History::class);
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
     * Get entity_id
     *
     * @return int
     */
    public function getEntityId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * Get from
     *
     * @return string
     */
    public function getFrom()
    {
        return $this->getData(self::FROM);
    }

    /**
     * Get to
     *
     * @return string
     */
    public function getTo()
    {
        return $this->getData(self::TO);
    }

    /**
     * Get subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->getData(self::SUBJECT);
    }

    /**
     * Get body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->getData(self::BODY);
    }

    /**
     * Get service_id
     *
     * @return int
     */
    public function getServiceId()
    {
        return $this->getData(self::SERVICE_ID);
    }

    /**
     * Get created_at
     *
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * Set entity_id
     *
     * @param int $entityId
     * @return \Swissup\Email\Api\Data\HistoryInterface
     */
    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * Set from
     *
     * @param string $from
     * @return \Swissup\Email\Api\Data\HistoryInterface
     */
    public function setFrom($from)
    {
        return $this->setData(self::FROM, $from);
    }

    /**
     * Set to
     *
     * @param string $to
     * @return \Swissup\Email\Api\Data\HistoryInterface
     */
    public function setTo($to)
    {
        return $this->setData(self::TO, $to);
    }

    /**
     * Set subject
     *
     * @param string $subject
     * @return \Swissup\Email\Api\Data\HistoryInterface
     */
    public function setSubject($subject)
    {
        return $this->setData(self::SUBJECT, $subject);
    }

    /**
     * Set body
     *
     * @param string $body
     * @return \Swissup\Email\Api\Data\HistoryInterface
     */
    public function setBody($body)
    {
        return $this->setData(self::BODY, $body);
    }

    /**
     * Set service_id
     *
     * @param int $serviceId
     * @return $this
     */
    public function setServiceId($serviceId)
    {
        return $this->setData(self::SERVICE_ID, $serviceId);
    }

    /**
     * Set created_at
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     *
     * @param  \Magento\Framework\Mail\MessageInterface $message
     */
    public function saveMessage($message)
    {
        /** @var \Magento\Framework\Mail\EmailMessage $message */
        $from = $message->getFrom();
        $mailAddresses = $from;
        if ($mailAddresses instanceof \Laminas\Mail\AddressList) {
            $_address = [];
            foreach ($mailAddresses as $mailAddress) {
                $_address[] = $mailAddress->toString();
            }
            $from = implode(',', $_address);
        } else {
            $from = (string) $from;
        }

        $to = $message->getTo();
        $mailAddresses = $to;
        if ($mailAddresses instanceof \Laminas\Mail\AddressList) {
            $_address = [];
            foreach ($mailAddresses as $mailAddress) {
                $_address[] = $mailAddress->toString();
            }
            $to = implode(',', $_address);
        } else {
            $to = implode(',', $message->getRecipients());
        }

        $subject = $message->getSubject();
        $encoding = $message->getEncoding();

        $subject = in_array($encoding, ['utf-8', 'UTF-8', 'ASCII']) ?
            $subject : mb_decode_mimeheader($subject);

        $body = $message->getBody();
        if ($body instanceof \Laminas\Mime\Message) {
            if ($body->isMultiPart()) {
                $body = $body->getPartContent(0);
            } else {
                $body = $body->generateMessage();
            }
        }

        if ($message instanceof \Laminas\Mail\Message) {
            $headers = $message->getHeaders();
            if ($headers->has('Content-Transfer-Encoding')) {
                $transferEncoding = $headers->get('Content-Transfer-Encoding');
                if ($transferEncoding instanceof \ArrayIterator) {
                    $transferEncoding = current($transferEncoding);
                }
                if ($transferEncoding instanceof \Laminas\Mail\Header\ContentTransferEncoding) {
                    $transferEncoding = $transferEncoding->getFieldValue();
                }
                // case '7bit':
                //      break;
                //  case '8bit':
                //      $body = quoted_printable_decode(imap_8bit($body));
                //      break;
                //  case 'binary':
                //      $body = imap_base64(imap_binary($body));
                //      break;
                //  case 'quoted-printable':
                //      $body = quoted_printable_decode($body);
                //      break;
                //  case 'base64':
                //      $body = imap_base64($body);
                //      break;
                // Convert to UTF-8 if necessary
                switch ($transferEncoding) {
                    case 'quoted-printable':
                        $body = quoted_printable_decode($body);
                        break;
                    case 'base64':
                        $body = base64_decode($body);
                        break;
                    default:
                        $body = $body;
                        break;
                }
            }
        }

        $this->addData([
            'from' => $from,
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
            'created_at' => date('c')
        ]);

        return $this->save();
    }
}
