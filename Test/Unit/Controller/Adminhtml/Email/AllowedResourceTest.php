<?php
declare(strict_types=1);

namespace Swissup\Email\Test\Unit\Controller\Adminhtml\Email;

use Magento\Backend\App\Action\Context;
use Magento\Framework\AuthorizationInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Swissup\Email\Controller\Adminhtml\Email\History\View;
use Swissup\Email\Controller\Adminhtml\Email\Service\Delete;

class AllowedResourceTest extends TestCase
{
    public static function controllerProvider(): array
    {
        return [
            'delete service' => [Delete::class, 'Swissup_Email::service_delete'],
            'view logged email' => [View::class, 'Swissup_Email::history'],
        ];
    }

    #[DataProvider('controllerProvider')]
    public function testControllerChecksExpectedResource(string $class, string $resource): void
    {
        $authorization = $this->createMock(AuthorizationInterface::class);
        $authorization->expects($this->once())->method('isAllowed')->with($resource)->willReturn(true);

        $controller = (new \ReflectionClass($class))->newInstanceWithoutConstructor();
        $property = new \ReflectionProperty(\Magento\Backend\App\AbstractAction::class, '_authorization');
        $property->setValue($controller, $authorization);

        $method = new \ReflectionMethod($class, '_isAllowed');
        $this->assertTrue($method->invoke($controller));
    }
}
