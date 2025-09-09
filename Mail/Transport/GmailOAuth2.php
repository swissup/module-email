<?php

declare(strict_types=1);

namespace Swissup\Email\Mail\Transport;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\Smtp\Auth\XOAuth2Authenticator;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use InvalidArgumentException;

/**
 * Gmail SMTP OAuth2 Transport
 */
class GmailOAuth2 extends EsmtpTransport
{
    private array $config;

    /**
     * Constructor method for initializing the class with necessary parameters.
     *
     * @param string $host The hostname or IP address to connect to.
     * @param int $port The port number for the connection.
     * @param bool $tls Flag indicating whether TLS is enabled for the connection.
     * @param EventDispatcherInterface|null $dispatcher Optional event dispatcher for handling events.
     * @param LoggerInterface|null $logger Optional logger instance for logging purposes.
     * @param array $config Optional array of configuration settings.
     *
     * @return void
     */
    public function __construct(
        string $host,
        int $port,
        bool $tls,
        EventDispatcherInterface $dispatcher = null,
        LoggerInterface $logger = null,
        array $config = []
    ) {
        parent::__construct($host, $port, $tls, $dispatcher, $logger);
        $this->config = $config;

        $this->setAuthenticators([]);
        $this->addAuthenticator(new XOAuth2Authenticator());
    }

    /**
     * Creates an instance of the class using a DSN (Data Source Name) configuration.
     *
     * @param Dsn $dsn The DSN object containing configuration information such as access token and username.
     * @param EventDispatcherInterface|null $dispatcher Optional event dispatcher for handling events within the instance.
     * @param LoggerInterface|null $logger Optional logger instance for logging operations.
     *
     * @return self Returns an instance of the class configured with the provided DSN and additional options.
     *
     * @throws \InvalidArgumentException If the DSN is missing required options or if the access token is expired.
     */
    public static function fromDsn(Dsn $dsn, EventDispatcherInterface $dispatcher = null, LoggerInterface $logger = null): self
    {
        $accessToken = $dsn->getOption('access_token');
        $expires = (int) $dsn->getOption('expires', 0);
        $username = $dsn->getOption('username');

        if (empty($accessToken) || empty($username)) {
            throw new \InvalidArgumentException(
                'Gmail OAuth2 DSN requires access_token and username options.'
            );
        }

        $bufferTime = 300;
        if ($expires > 0 && time() > ($expires + $bufferTime)) {
            throw new \InvalidArgumentException('Access token is expired.');
        }

        $config = [
            'username' => $username,
            'access_token' => $accessToken,
            'expires' => $expires
        ];

        return new self('smtp.gmail.com', 587, true, $dispatcher, $logger, $config);
    }

    /**
     * Initializes the necessary configurations and starts the process.
     *
     * @return void
     */
    public function start(): void
    {
        $this->setUsername($this->config['username']);
        $this->setPassword($this->config['access_token']);

        parent::start();
    }

    /**
     * Converts the class instance to a string representation.
     *
     * @return string A formatted string containing the connection details, including username and server information.
     */
    public function __toString(): string
    {
        return sprintf('gmail+oauth2://%s@smtp.gmail.com:587', $this->config['username'] ?? 'unknown');
    }
}
