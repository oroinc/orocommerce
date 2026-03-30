<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\DraftSession;

use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\EventListener\DraftSession\LoadOrderDraftOnRequestListener;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class LoadOrderDraftOnRequestListenerTest extends TestCase
{
    private OrderDraftManager&MockObject $orderDraftManager;
    private AuthorizationCheckerInterface&MockObject $authorizationChecker;
    private LoadOrderDraftOnRequestListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->orderDraftManager = $this->createMock(OrderDraftManager::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->listener = new LoadOrderDraftOnRequestListener(
            $this->orderDraftManager,
            $this->authorizationChecker
        );
    }

    public function testOnKernelRequestDoesNothingForSubRequest(): void
    {
        $request = new Request();
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        $this->orderDraftManager
            ->expects(self::never())
            ->method('getDraftSessionUuid');

        $this->listener->onKernelRequest($event);

        self::assertFalse($event->getRequest()->attributes->has(LoadOrderDraftOnRequestListener::ORDER_DRAFT));
    }

    public function testOnKernelRequestDoesNothingWhenNoDraftSessionUuid(): void
    {
        $request = new Request();
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->orderDraftManager
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn(null);

        $this->orderDraftManager
            ->expects(self::never())
            ->method('findOrderDraft');

        $this->listener->onKernelRequest($event);

        self::assertFalse($event->getRequest()->attributes->has(LoadOrderDraftOnRequestListener::ORDER_DRAFT));
    }

    public function testOnKernelRequestDoesNothingWhenDraftSessionUuidIsEmptyString(): void
    {
        $request = new Request();
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->orderDraftManager
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn('');

        $this->orderDraftManager
            ->expects(self::never())
            ->method('findOrderDraft');

        $this->listener->onKernelRequest($event);

        self::assertFalse($event->getRequest()->attributes->has(LoadOrderDraftOnRequestListener::ORDER_DRAFT));
    }

    public function testOnKernelRequestSetsNullWhenOrderDraftNotFound(): void
    {
        $draftSessionUuid = 'test-uuid-123';
        $request = new Request();
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->orderDraftManager
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn($draftSessionUuid);

        $this->orderDraftManager
            ->expects(self::once())
            ->method('findOrderDraft')
            ->with($draftSessionUuid)
            ->willReturn(null);

        $this->authorizationChecker
            ->expects(self::never())
            ->method('isGranted');

        $this->listener->onKernelRequest($event);

        self::assertTrue($event->getRequest()->attributes->has(LoadOrderDraftOnRequestListener::ORDER_DRAFT));
        self::assertNull($event->getRequest()->attributes->get(LoadOrderDraftOnRequestListener::ORDER_DRAFT));
    }

    public function testOnKernelRequestThrowsAccessDeniedWhenNotAuthorized(): void
    {
        $draftSessionUuid = 'test-uuid-456';
        $orderDraft = new Order();
        ReflectionUtil::setId($orderDraft, 789);

        $request = new Request();
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->orderDraftManager
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn($draftSessionUuid);

        $this->orderDraftManager
            ->expects(self::once())
            ->method('findOrderDraft')
            ->with($draftSessionUuid)
            ->willReturn($orderDraft);

        $this->authorizationChecker
            ->expects(self::once())
            ->method('isGranted')
            ->with('oro_order_update', $orderDraft)
            ->willReturn(false);

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Access denied to the order draft entity with UUID test-uuid-456');

        $this->listener->onKernelRequest($event);
    }

    public function testOnKernelRequestSetsOrderDraftWhenAuthorized(): void
    {
        $draftSessionUuid = 'test-uuid-789';
        $orderDraft = new Order();
        ReflectionUtil::setId($orderDraft, 456);

        $request = new Request();
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->orderDraftManager
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn($draftSessionUuid);

        $this->orderDraftManager
            ->expects(self::once())
            ->method('findOrderDraft')
            ->with($draftSessionUuid)
            ->willReturn($orderDraft);

        $this->authorizationChecker
            ->expects(self::once())
            ->method('isGranted')
            ->with('oro_order_update', $orderDraft)
            ->willReturn(true);

        $this->listener->onKernelRequest($event);

        self::assertTrue($event->getRequest()->attributes->has(LoadOrderDraftOnRequestListener::ORDER_DRAFT));
        self::assertSame(
            $orderDraft,
            $event->getRequest()->attributes->get(LoadOrderDraftOnRequestListener::ORDER_DRAFT)
        );
    }
}
