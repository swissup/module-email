<?php
declare(strict_types=1);

namespace Swissup\Email\Mail;

use Magento\Framework\Mail\EmailMessageInterface as FrameworkEmailMessageInterface;
use Laminas\Mail\Message;

/**
 * Interface EmailMessageInterface
 */
interface EmailMessageInterface extends FrameworkEmailMessageInterface
{
    /**
     * @return Message
     */
    public function getZendMessage();
}
