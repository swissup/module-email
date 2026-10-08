<?php
declare(strict_types=1);

namespace Swissup\Email\Test\Unit\Model;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Swissup\Email\Model\Service;

class ServiceDsnTest extends TestCase
{
    public static function secureProvider(): array
    {
        return [
            'none' => [Service::TYPE_SMTP, Service::SECURE_NONE, 'smtp://u:p@mail.test:25'],
            'ssl is implicit tls' => [Service::TYPE_SMTP, Service::SECURE_SSL, 'smtps://u:p@mail.test:25'],
            'tls is marked as mandatory starttls' => [
                Service::TYPE_SMTP,
                Service::SECURE_TLS,
                'smtp://u:p@mail.test:25?encryption=tls',
            ],
            'ssl for mandrill' => [Service::TYPE_MANDRILL, Service::SECURE_SSL, 'smtps://u:p@mail.test:25'],
        ];
    }

    #[DataProvider('secureProvider')]
    public function testSecureOptionIsReflectedInDsn(int $type, int $secure, string $expected): void
    {
        $service = (new \ReflectionClass(Service::class))->newInstanceWithoutConstructor();
        $service->setData([
            'type' => $type,
            'host' => 'mail.test',
            'port' => 25,
            'user' => 'u',
            'password' => 'p',
            'secure' => $secure,
        ]);

        $this->assertSame($expected, $service->getDsn());
    }
}
