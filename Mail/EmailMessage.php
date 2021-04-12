<?php
declare(strict_types=1);
/**
 * @author        Wilfried Wolf <wilfried.wolf@sandstein.de>
 */

namespace Swissup\Email\Mail;

use Laminas\Mail\Message;
use Magento\Framework\Mail\EmailMessage as FrameworkEmailMessage;
use Magento\Framework\Mail\EmailMessageInterface;

/**
 * Class EmailMessage
 * @package Swissup\Email\Mail
 */
class EmailMessage extends FrameworkEmailMessage implements EmailMessageInterface
{
    /**
     * @return Message
     */
    public function getZendMessage(): Message
    {
        return $this->zendMessage;
    }

}
