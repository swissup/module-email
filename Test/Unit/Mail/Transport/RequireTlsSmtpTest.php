<?php
declare(strict_types=1);

namespace Swissup\Email\Test\Unit\Mail\Transport;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Swissup\Email\Mail\Transport\RequireTlsSmtp;
use Symfony\Component\Mailer\Exception\TransportException;

class RequireTlsSmtpTest extends TestCase
{
    /** @var resource|null */
    private $server;

    /** @var resource|null */
    private $peer;

    protected function tearDown(): void
    {
        foreach ([$this->peer, $this->server] as $socket) {
            if (is_resource($socket)) {
                fclose($socket);
            }
        }
    }

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

    public function testCommandsAreAllowedAfterStartTls(): void
    {
        $transport = $this->connect("220 Ready to start TLS\r\n235 Authenticated\r\n");

        $transport->executeCommand("STARTTLS\r\n", [220]);

        $this->assertSame(
            "235 Authenticated\r\n",
            $transport->executeCommand("AUTH PLAIN dQB1AHA=\r\n", [235])
        );
    }

    public function testCommandsAreRefusedWhenStartTlsIsRejected(): void
    {
        $transport = $this->connect("454 TLS not available\r\n");

        try {
            $transport->executeCommand("STARTTLS\r\n", [220]);
            $this->fail('STARTTLS rejection was not reported.');
        } catch (TransportException $e) {
            $this->assertStringNotContainsString('TLS is required', $e->getMessage());
        }

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('TLS is required');
        $transport->executeCommand("AUTH PLAIN dQB1AHA=\r\n", [235]);
    }

    public function testStartTlsStateIsResetOnReconnect(): void
    {
        $transport = $this->connect("220 Ready to start TLS\r\n");
        $transport->executeCommand("STARTTLS\r\n", [220]);

        // The reconnect fails, but must not carry the TLS state of the old connection.
        fclose($this->server);
        try {
            $transport->start();
        } catch (TransportException) {
        }

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('TLS is required');
        $transport->executeCommand("AUTH PLAIN dQB1AHA=\r\n", [235]);
    }

    /**
     * Connect a plain (non-TLS, port != 465) transport to a loopback server
     * that has already queued the given replies.
     */
    private function connect(string $replies): RequireTlsSmtp
    {
        $this->server = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if (!$this->server) {
            $this->markTestSkipped("Unable to open a loopback socket: $errstr");
        }
        $port = (int) substr(strrchr(stream_socket_get_name($this->server, false), ':'), 1);

        $transport = new RequireTlsSmtp('127.0.0.1', $port, false);
        $stream = $transport->getStream();
        $stream->setTimeout(2);
        $stream->initialize();

        $this->peer = stream_socket_accept($this->server, 2);
        fwrite($this->peer, $replies);

        return $transport;
    }
}
