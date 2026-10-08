<?php
declare(strict_types=1);

namespace Swissup\Email\Test\Unit\Mail\Transport;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Swissup\Email\Mail\Transport\RequireTlsSmtp;
use Symfony\Component\Mailer\Exception\TransportException;

class RequireTlsSmtpTest extends TestCase
{
    public static function commandProvider(): array
    {
        return [
            'auth plain' => ["AUTH PLAIN dQB1AHA=\r\n"],
            'auth login' => ["AUTH LOGIN\r\n"],
            'mail from' => ["MAIL FROM:<a@example.test>\r\n"],
            'data' => ["DATA\r\n"],
        ];
    }

    #[DataProvider('commandProvider')]
    public function testCommandsAreRefusedWithoutTls(string $command): void
    {
        $transport = new RequireTlsSmtp('127.0.0.1', 1, false);

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('TLS is required');
        $transport->executeCommand($command, [250]);
    }
}
