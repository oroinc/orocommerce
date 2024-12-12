<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\CheckoutBundle\Event\CheckoutTransitionBeforeEvent;
use Oro\Bundle\CheckoutBundle\EventListener\CheckoutCsrfProtectionListener;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CheckoutCsrfProtectionListenerTest extends TestCase
{
    private CsrfRequestManager|MockObject $csrfRequestManager;
    private RequestStack|MockObject $requestStack;
    private CheckoutCsrfProtectionListener $listener;

    protected function setUp(): void
    {
        $this->csrfRequestManager = $this->createMock(CsrfRequestManager::class);
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->listener = new CheckoutCsrfProtectionListener($this->csrfRequestManager, $this->requestStack);
    }

    public function testOnTransitionBeforeWithoutRequest(): void
    {
        $this->csrfRequestManager
            ->expects($this->never())
            ->method('isRequestTokenValid');

        $this->requestStack
            ->expects($this->once())
            ->method('getMainRequest')
            ->willReturn(null);

        $event = $this->createMock(CheckoutTransitionBeforeEvent::class);
        $this->listener->onTransitionBefore($event);
    }

    public function testOnTransitionBeforeWithValidToken(): void
    {
        $csrfProtection = new CsrfProtection(true);
        $request = new Request(attributes: ['_' . CsrfProtection::ALIAS_NAME => $csrfProtection]);

        $this->requestStack
            ->expects($this->once())
            ->method('getMainRequest')
            ->willReturn($request);

        $this->csrfRequestManager
            ->expects($this->once())
            ->method('isRequestTokenValid')
            ->with($request)
            ->willReturn(true);

        $event = $this->createMock(CheckoutTransitionBeforeEvent::class);

        $this->listener->onTransitionBefore($event);
    }

    public function testOnTransitionBeforeWithInvalidToken(): void
    {
        $csrfProtection = new CsrfProtection(true);
        $request = new Request(attributes: ['_' . CsrfProtection::ALIAS_NAME => $csrfProtection]);

        $this->requestStack
            ->expects($this->once())
            ->method('getMainRequest')
            ->willReturn($request);

        $this->csrfRequestManager
            ->expects($this->once())
            ->method('isRequestTokenValid')
            ->with($request)
            ->willReturn(false);

        $event = $this->createMock(CheckoutTransitionBeforeEvent::class);
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Invalid CSRF token');

        $this->listener->onTransitionBefore($event);
    }
}
