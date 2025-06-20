<?php

namespace Swissup\Email\Service;

class EmailTestService
{
    /**
     * @var \Swissup\Email\Mail\TransportFactory
     */
    private $transportFactory;

    /**
     * @var string|null
     */
    private $from;

    /**
     * @var \Swissup\Email\Api\Data\ServiceInterface|null
     */
    private $service;

    /**
     * @var string|null
     */
    private $webtesterEmail;

    /**
     * @var string|null
     */
    private $verificationCode;

    /**
     * @param $transportFactory
     */
    public function __construct(\Swissup\Email\Mail\TransportFactory $transportFactory)
    {
        $this->transportFactory = $transportFactory;
    }

    /**
     * @param $from
     * @return $this
     */
    public function setFrom($from): self
    {
        $this->from = $from;
        return $this;
    }

    /**
     * Retrieves the sender's email address.
     *
     * @return string The sender's email address, defaulting to 'john@doe.com' if not set.
     */
    public function getFrom()
    {
        return $this->from ?? 'john@doe.com';
    }

    /**
     * Sets the service.
     *
     * @param \Swissup\Email\Api\Data\ServiceInterface $service The service to be set.
     * @return self Returns the current instance.
     */
    public function setService(\Swissup\Email\Api\Data\ServiceInterface $service): self
    {
        $this->service = $service;
        return $this;
    }

    /**
     * Sends a verification email with a unique code to a specified recipient.
     *
     * @return self Returns the current instance of the class.
     */
    public function send(): self
    {
        $code = $this->getVerificationCode();
        $mailMessage = new \Magento\Framework\Mail\Message();
        $messageText = "This is test transport mail. Verification code : {$code} .";
        $mailMessage->setBodyHtml("
            <html>
                <body>
                    {$messageText}
                </body>
            </html>
        ");
        $from = $this->getFrom();
        $mailMessage->setFromAddress($from, $from);
        $to = $this->getWebtesterEmail();
        $mailMessage->addTo($to);
        $mailMessage->setSubject("Test Email Transport ({$code})");
        /* @var $transport \Swissup\Email\Mail\Transport */
        $transport = $this->transportFactory->create(['message' => $mailMessage]);
        $transport->setService($this->service);
        $transport->sendMessage();
        return $this;
    }

    /**
     * Generates a success message indicating a successful connection with the mail server and provides verification instructions.
     *
     * @return string Returns the success message containing the verification details.
     */
    public function getSuccessMessage(): string
    {
        $code = $this->getVerificationCode();
        $email = $this->getWebtesterEmail();
        $link = $this->getLink();
        return 'Connection with mail server was successfully established.'
            . " Please check your inbox {$email} to verify."
            . " Verification code : {$code}."
            . " Or click here: <a href=\"{$link}\">here</a> ";
    }

    /**
     * Retrieves the web tester email address. If the email has not been generated yet,
     * it creates one based on a verification code and a specific placeholder format.
     *
     * @return string The generated or retrieved web tester email address.
     */
    private function getWebtesterEmail()
    {
        if ($this->webtesterEmail === null) {
            $code = $this->getVerificationCode();
            $placeholder = str_repeat('x', 9);
            $inbox = str_replace($placeholder, $code, 'test-' . $placeholder);
            $this->webtesterEmail = $inbox . '@srv1.mail-tester.com';
        }
        return $this->webtesterEmail;
    }

    /**
     * Generates a link for testing the email using the mail-tester service.
     *
     * @return string The generated mail-tester link.
     */
    private function getLink()
    {
        $webTesterEmail = $this->getWebtesterEmail();
        list($inbox, $domain) = explode('@', $webTesterEmail);
        return "https://www.mail-tester.com/" . $inbox;
    }

    /**
     * @return string|null
     */
    private function getVerificationCode()
    {
        if ($this->verificationCode === null) {
            $code = (float)rand()/(float)getrandmax();
            $code = (string) $code;
            $code = trim($code);
            if (substr($code, 0, 2) === "0.") {
                $code = substr($code, 2);
            }
            $code = base_convert($code, 10, 36);
            // $code .= '1234567';
            $code = substr($code, 2, 11);
            $this->verificationCode = $code;
        }
        return $this->verificationCode;
    }

    /**
     * Sets the verification code to be used.
     *
     * @param string $code The verification code to set.
     * @return self The instance of the current object for method chaining.
     */
    public function setVerificationCode($code): self
    {
        $this->verificationCode = $code;
        return $this;
    }
}
