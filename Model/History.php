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
     * @param \Magento\Framework\Mail\MessageInterface $message
     */
    public function saveMessage($message)
    {
        /** @var \Magento\Framework\Mail\EmailMessage $message */

        // normalize addresses
        $from = $this->formatAddresses($message->getFrom());
        $to   = $this->formatAddresses($message->getTo());

        // subject
        $encoding = $this->extractEncoding($message);
        $subject  = (string) $message->getSubject();
        $subject  = in_array($encoding, ['utf-8', 'UTF-8', 'ASCII'], true)
            ? $subject
            : mb_decode_mimeheader($subject);

        // body
        $body = $this->extractBody($message);

        $this->addData([
            'from'       => $from,
            'to'         => $to,
            'subject'    => $subject,
            'body'       => $body,
            'created_at' => date('c'),
        ]);

        return $this->save();
    }

    /**
     * extarct Format addresses
     * @param $addresses
     * @return string
     */
    private function formatAddresses($addresses): string
    {
        if (empty($addresses)) {
            return '';
        }

        $result = [];
        foreach ($addresses as $addr) {
            if ($addr instanceof \Magento\Framework\Mail\Address) {
                $result[] = $addr->getName()
                    ? sprintf('%s <%s>', $addr->getName(), $addr->getEmail())
                    : $addr->getEmail();
            } elseif (is_string($addr)) {
                $result[] = $addr;
            } elseif ($addr instanceof \Symfony\Component\Mime\Address) {
                $result[] = $addr->getName()
                    ? sprintf('%s <%s>', $addr->getName(), $addr->getAddress())
                    : $addr->getAddress();
            }
        }

        return implode(',', $result);
    }

    /**
     * Extracts the encoding from the given email message.
     * @param \Magento\Framework\Mail\EmailMessage $message
     * @return string
     */
    private function extractEncoding(\Magento\Framework\Mail\EmailMessage $message): string
    {
        $encoding = null;
        try {
            $encoding = $message->getEncoding();
        } catch (\Throwable $e) {
            $encoding = null;
        }
        return $encoding ?: 'utf-8';
    }

    /**
     * Extracts the body content from the given email message.
     *
     * @param \Magento\Framework\Mail\EmailMessage $message The email message from which to extract the body content.
     * @return string The extracted body content as a string. If the body cannot be extracted, returns an empty string or an error message.
     */
    private function extractBody(\Magento\Framework\Mail\EmailMessage $message): string
    {
        try {
            $body = $message->getBody();

            if ($body instanceof \Symfony\Component\Mime\Part\TextPart) {
                return $body->getBody(); // або $body->getContent()
            }

            if ($body instanceof \Symfony\Component\Mime\Part\AbstractMultipartPart) {
                $content = '';
                foreach ($body->getParts() as $part) {
                    if ($part instanceof \Symfony\Component\Mime\Part\TextPart) {
                        $content .= "\n" . $part->getBody();
                    }
                }
                return trim($content);
            }

            if (is_string($body)) {
                return $body;
            }

            // fallback
            if (method_exists($message, 'getBodyText')) {
                $text = $message->getBodyText();
                if (!empty($text)) {
                    return $text;
                }
            }
            if (method_exists($message, 'getBodyHtml')) {
                $html = $message->getBodyHtml();
                if (!empty($html)) {
                    return $html;
                }
            }

        } catch (\Throwable $e) {
            return '[Body extraction failed: ' . $e->getMessage() . ']';
        }

        return '';
    }
}
