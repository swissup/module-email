<?php
declare(strict_types=1);

namespace Swissup\Email\Test\Unit\Controller\Adminhtml\Email;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Swissup\Email\Controller\Adminhtml\Email\Service;

class ServiceAclTest extends TestCase
{
    public static function controllerProvider(): array
    {
        return [
            'inline edit' => [Service\InlineEdit::class, 'Swissup_Email::service_save'],
            'mass enable' => [Service\MassEnable::class, 'Swissup_Email::service_save'],
            'mass disable' => [Service\MassDisable::class, 'Swissup_Email::service_save'],
            'mass delete' => [Service\MassDelete::class, 'Swissup_Email::service_delete'],
        ];
    }

    #[DataProvider('controllerProvider')]
    public function testControllerDeclaresOwnAclResource(string $class, string $expected): void
    {
        $this->assertSame($expected, constant($class . '::ADMIN_RESOURCE'));
    }
}
