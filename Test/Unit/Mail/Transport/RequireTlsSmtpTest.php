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

    /** @var resource|null */
    private $process;

    /** @var resource[] */
    private array $pipes = [];

    /** @var string|null */
    private ?string $certFile = null;

    protected function tearDown(): void
    {
        foreach ([$this->peer, $this->server, ...$this->pipes] as $socket) {
            if (is_resource($socket)) {
                fclose($socket);
            }
        }
        if (is_resource($this->process)) {
            // phpcs:ignore Magento2.Security.InsecureFunction -- test-only SMTP server child process
            proc_terminate($this->process);
            // phpcs:ignore Magento2.Security.InsecureFunction -- test-only SMTP server child process
            proc_close($this->process);
        }
        if ($this->certFile) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            unlink($this->certFile);
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
            $this->fail('Reconnecting to a closed server did not fail.');
        } catch (TransportException $e) {
            $this->assertStringContainsString('Connection could not be established', $e->getMessage());
        }

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('TLS is required');
        $transport->executeCommand("AUTH PLAIN dQB1AHA=\r\n", [235]);
    }

    public function testStartAuthenticatesAfterStartTlsHandshake(): void
    {
        $transport = $this->spawnServer([
            "220 mx.test ESMTP\r\n",
            "250-mx.test\r\n250-STARTTLS\r\n250 AUTH PLAIN\r\n",
            "220 Ready to start TLS\r\n",
            'TLS',
            "250-mx.test\r\n250 AUTH PLAIN\r\n",
            "235 Authenticated\r\n",
            "221 Bye\r\n",
        ], $this->createCertificate());

        $transport->start();
        $transport->stop();

        $this->assertMatchesRegularExpression(
            '/^> STARTTLS\nTLS\n> EHLO .*\n> AUTH PLAIN .*\n> QUIT$/m',
            $this->serverLog()
        );
    }

    public function testStartRefusesAuthWhenStartTlsIsNotOffered(): void
    {
        $transport = $this->spawnServer([
            "220 mx.test ESMTP\r\n",
            "250-mx.test\r\n250 AUTH PLAIN\r\n",
        ]);

        try {
            $transport->start();
            $this->fail('Authentication was attempted without TLS.');
        } catch (TransportException $e) {
            $this->assertStringContainsString('TLS is required', $e->getMessage());
        }

        $this->assertStringNotContainsString('AUTH', $this->serverLog($transport));
    }

    public function testCommandsAreRefusedAfterFailedStartTlsHandshake(): void
    {
        $transport = $this->spawnServer([
            "220 mx.test ESMTP\r\n",
            "250-mx.test\r\n250-STARTTLS\r\n250 AUTH PLAIN\r\n",
            "220 Ready to start TLS\r\n",
            'CLOSE',
        ]);

        try {
            $transport->start();
            $this->fail('A failed STARTTLS handshake was not reported.');
        } catch (TransportException $e) {
            $this->assertStringContainsString('STARTTLS', $e->getMessage());
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

    /**
     * Start a scripted SMTP server in a child process (see _files/smtp-server.php)
     * and return a plain (non-TLS, port != 465) transport with credentials for it.
     */
    private function spawnServer(array $steps, ?string $cert = null): RequireTlsSmtp
    {
        // phpcs:ignore Magento2.Security.InsecureFunction -- test-only SMTP server child process
        $this->process = proc_open(
            [PHP_BINARY, __DIR__ . '/_files/smtp-server.php', $cert ?? '-', json_encode($steps)],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $this->pipes
        );
        $address = is_resource($this->process) ? trim((string) fgets($this->pipes[1])) : '';
        if (!$address) {
            $this->markTestSkipped('Unable to start the SMTP test server.');
        }

        $transport = new RequireTlsSmtp('127.0.0.1', (int) substr(strrchr($address, ':'), 1), false);
        $transport->setUsername('user');
        $transport->setPassword('pass');
        $transport->getStream()
            ->setTimeout(5)
            ->setStreamOptions(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);

        return $transport;
    }

    /**
     * Close the client side and return what the SMTP test server received.
     */
    private function serverLog(?RequireTlsSmtp $transport = null): string
    {
        $transport?->getStream()->terminate();

        return (string) stream_get_contents($this->pipes[1]);
    }

    private function createCertificate(): string
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048]);
        $csr = $key ? openssl_csr_new(['commonName' => 'mx.test'], $key) : false;
        $cert = $csr ? openssl_csr_sign($csr, null, $key, 1) : false;
        if (!$cert || !openssl_x509_export($cert, $certPem) || !openssl_pkey_export($key, $keyPem)) {
            $this->markTestSkipped('Unable to create a test certificate.');
        }

        $this->certFile = (string) tempnam(sys_get_temp_dir(), 'smtp-cert');
        file_put_contents($this->certFile, $certPem . $keyPem);

        return $this->certFile;
    }
}
