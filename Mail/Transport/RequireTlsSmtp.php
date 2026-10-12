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
    /**
     * SocketStream::isTLS() reports implicit TLS (port 465) only and stays false
     * after a STARTTLS upgrade, so the upgrade is tracked here.
     *
     * @var bool
     */
    private bool $startTlsAccepted = false;

    /**
     * @inheritdoc
     */
    public function start(): void
    {
        $this->startTlsAccepted = false;

        try {
            parent::start();
        } catch (\Throwable $e) {
            // e.g. the server accepted STARTTLS but the handshake failed: the socket is still plain
            $this->startTlsAccepted = false;
            throw $e;
        }
    }

    /**
     * @inheritdoc
     */
    public function executeCommand(string $command, array $codes): string
    {
        if (!$this->startTlsAccepted
            && !$this->getStream()->isTLS()
            && !preg_match('/^(EHLO|HELO|STARTTLS|RSET|QUIT)\b/i', $command)
        ) {
            throw new TransportException('Unable to send mail: TLS is required but could not be established.');
        }

        $response = parent::executeCommand($command, $codes);

        if (preg_match('/^STARTTLS\b/i', $command)) {
            // EsmtpTransport enables crypto right after the 220 reply and throws
            // if that fails, so no command can follow over a plain connection.
            $this->startTlsAccepted = true;
        }

        return $response;
    }
}
