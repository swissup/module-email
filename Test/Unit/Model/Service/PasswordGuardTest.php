<?php
declare(strict_types=1);

namespace Swissup\Email\Test\Unit\Model\Service;

use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Swissup\Email\Model\Service;
use Swissup\Email\Model\Service\PasswordGuard;

class PasswordGuardTest extends TestCase
{
    private function stored(?int $id = 5): Service
    {
        $service = (new \ReflectionClass(Service::class))->newInstanceWithoutConstructor();
        $service->setData(['id' => $id, 'host' => 'smtp.test', 'port' => 587, 'user' => 'u', 'password' => 'secret']);

        return $service;
    }

    public function testEmptyPasswordIsDroppedWhenTargetIsUnchanged(): void
    {
        $data = ['host' => 'smtp.test', 'port' => '587', 'user' => 'u', 'password' => '', 'name' => 'x'];

        $result = (new PasswordGuard())->apply($this->stored(), $data);

        $this->assertArrayNotHasKey('password', $result);
        $this->assertSame('x', $result['name']);
    }

    public static function changedTargetProvider(): array
    {
        return [
            'host' => [['host' => 'evil.test', 'port' => '587', 'user' => 'u']],
            'port' => [['host' => 'smtp.test', 'port' => '25', 'user' => 'u']],
            'user' => [['host' => 'smtp.test', 'port' => '587', 'user' => 'other']],
        ];
    }

    #[DataProvider('changedTargetProvider')]
    public function testEmptyPasswordIsRejectedWhenTargetChanges(array $data): void
    {
        $this->expectException(LocalizedException::class);

        (new PasswordGuard())->apply($this->stored(), $data + ['password' => '']);
    }

    public function testNewPasswordIsAlwaysAccepted(): void
    {
        $data = ['host' => 'other.test', 'password' => 'new'];

        $this->assertSame($data, (new PasswordGuard())->apply($this->stored(), $data));
    }

    public function testNewServiceIsNotAffected(): void
    {
        $data = ['host' => 'other.test', 'password' => ''];

        $this->assertSame($data, (new PasswordGuard())->apply($this->stored(null), $data));
    }
}
