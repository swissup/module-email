<?php

namespace Swissup\Email\Mail\Message\Convertor;

use Zend_Mime;

class Zend1FakeTransport extends \Zend_Mail_Transport_Abstract
{
    /**
     * Don't send mail
     *
     * @access public
     * @return void
     */
    public function _sendMail() //phpcs:ignore Magento2.CodeAnalysis.EmptyBlock
    {
    }

    /**
     *
     * @return string
     */
    public function getRawMessage()
    {
        return $this->toString();
    }

    /**
     * Serialize to string
     *
     * @return string
     */
    public function toString()
    {
        return $this->header . Zend_Mime::LINEEND . $this->body;
    }
}
