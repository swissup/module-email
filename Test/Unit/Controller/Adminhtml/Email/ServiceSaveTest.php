<?php
declare(strict_types=1);

namespace Swissup\Email\Test\Unit\Controller\Adminhtml\Email;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Message\ManagerInterface;
use PHPUnit\Framework\TestCase;
use Swissup\Email\Controller\Adminhtml\Email\Service\Check;
use Swissup\Email\Controller\Adminhtml\Email\Service\Save;
use Swissup\Email\Model\Service;
use Swissup\Email\Model\ServiceRepository;

class ServiceSaveTest extends TestCase
{
    public function testRedirectUrlAndTokenFieldsFromRequestAreIgnored(): void
    {
        $request = $this->createMock(Http::class);
        $request->method('getPostValue')->willReturn([
            'name' => 'x',
            'callback_url' => 'https://evil.test/',
            'token_id' => '99',
            'token' => ['access_token' => 'a'],
        ]);
        $request->method('getParam')->willReturn(0);

        $redirect = $this->createMock(Redirect::class);
        $redirect->expects($this->never())->method('setUrl');
        $redirect->method('setPath')->willReturnSelf();
        $redirectFactory = $this->createMock(RedirectFactory::class);
        $redirectFactory->method('create')->willReturn($redirect);

        $context = $this->createMock(Context::class);
        $context->method('getRequest')->willReturn($request);
        $context->method('getResultRedirectFactory')->willReturn($redirectFactory);
        $context->method('getMessageManager')->willReturn($this->createMock(ManagerInterface::class));
        $context->method('getSession')->willReturn(
            $this->getMockBuilder(\Magento\Backend\Model\Session::class)->disableOriginalConstructor()
                ->addMethods(['setFormData'])->getMock()
        );

        $service = (new \ReflectionClass(Service::class))->newInstanceWithoutConstructor();
        $repository = $this->createMock(ServiceRepository::class);
        $repository->method('create')->willReturn($service);
        $repository->expects($this->once())->method('save')->with($this->callback(
            fn(Service $saved): bool => !$saved->hasData('callback_url')
                && !$saved->hasData('token_id')
                && !$saved->hasData('token')
        ));

        (new Save($context, $repository))->execute();
    }

    public function testCheckAcceptsPostOnly(): void
    {
        $this->assertInstanceOf(
            \Magento\Framework\App\Action\HttpPostActionInterface::class,
            (new \ReflectionClass(Check::class))->newInstanceWithoutConstructor()
        );
    }
}
