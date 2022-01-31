<?php
declare(strict_types=1);

namespace Swissup\Email\Mail;

use Magento\Framework\Mail\EmailMessageInterface as FrameworkEmailMessageInterface;

/**
 * Interface EmailMessageInterface
 */
interface EmailMessageInterface extends FrameworkEmailMessageInterface
{
    /**
     * @return \Laminas\Mail\Message
     */
    public function getZendMessage();
}
