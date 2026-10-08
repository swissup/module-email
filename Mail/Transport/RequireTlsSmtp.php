<?php

declare(strict_types=1);

namespace Swissup\Email\Mail\Transport;

use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

/**
 * SMTP transport that refuses to send credentials or mail over a plain connection.
 *
 * The stock transport upgrades to TLS only when the server offers STARTTLS and
 * silently continues in clear text otherwise, so an attacker who strips the
 * STARTTLS capability from the server reply receives the login and the message.
 */
class RequireTlsSmtp extends EsmtpTransport
{
    public function executeCommand(string $command, array $codes): string
    {
        if (!$this->getStream()->isTLS() && !preg_match('/^(EHLO|HELO|STARTTLS|RSET|QUIT)\b/i', $command)) {
            throw new TransportException('Unable to send mail: TLS is required but could not be established.');
        }

        return parent::executeCommand($command, $codes);
    }
}
