<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\DraftSession;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\EventListener\DraftSession\SyncOrderToOrderDraftOnOrderEventListener;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;

final class SyncOrderToOrderDraftOnOrderEventListenerTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private OrderDraftManager&MockObject $orderDraftManager;
    private EntityManagerInterface&MockObject $entityManager;
    private SyncOrderToOrderDraftOnOrderEventListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->orderDraftManager = $this->createMock(OrderDraftManager::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->listener = new SyncOrderToOrderDraftOnOrderEventListener(
            $this->doctrine,
            $this->orderDraftManager
        );
    }

    public function testOnOrderEventWhenDraftSessionUuidIsNull(): void
    {
        $form = $this->createMock(FormInterface::class);
        $order = new Order();
        $event = new OrderEvent($form, $order);

        $this->orderDraftManager
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn(null);

        $this->doctrine
            ->expects(self::never())
            ->method('getManagerForClass');

        $this->listener->onOrderEvent($event);
    }

    public function testOnOrderEventWhenDraftSessionUuidIsEmpty(): void
    {
        $form = $this->createMock(FormInterface::class);
        $order = new Order();
        $event = new OrderEvent($form, $order);

        $this->orderDraftManager
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn('');

        $this->doctrine
            ->expects(self::never())
            ->method('getManagerForClass');

        $this->listener->onOrderEvent($event);
    }

    public function testOnOrderEventWhenFormIsNotSubmitted(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form
            ->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(false);

        $order = new Order();
        $event = new OrderEvent($form, $order);

        $this->orderDraftManager
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn('test-uuid-123');

        $this->doctrine
            ->expects(self::never())
            ->method('getManagerForClass');

        $this->orderDraftManager
            ->expects(self::never())
            ->method('synchronizeEntityToDraft');

        $this->listener->onOrderEvent($event);
    }

    public function testOnOrderEventWhenOrderDraftNotFound(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form
            ->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);

        $order = new Order();
        ReflectionUtil::setId($order, 123);
        $event = new OrderEvent($form, $order);

        $this->orderDraftManager
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn('test-uuid-123');

        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(Order::class)
            ->willReturn($this->entityManager);

        $this->entityManager
            ->expects(self::once())
            ->method('clear');

        $this->orderDraftManager
            ->expects(self::once())
            ->method('findOrderDraft')
            ->with('test-uuid-123')
            ->willReturn(null);

        $this->orderDraftManager
            ->expects(self::never())
            ->method('synchronizeEntityToDraft');

        $this->entityManager
            ->expects(self::never())
            ->method('flush');

        $this->listener->onOrderEvent($event);
    }

    public function testOnOrderEventWhenOrderDraftFound(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form
            ->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);

        $order = new Order();
        ReflectionUtil::setId($order, 456);

        $orderDraft = new Order();
        ReflectionUtil::setId($orderDraft, 789);

        $event = new OrderEvent($form, $order);

        $this->orderDraftManager
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn('valid-draft-uuid');

        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(Order::class)
            ->willReturn($this->entityManager);

        $this->entityManager
            ->expects(self::once())
            ->method('clear');

        $this->orderDraftManager
            ->expects(self::once())
            ->method('findOrderDraft')
            ->with('valid-draft-uuid')
            ->willReturn($orderDraft);

        $this->orderDraftManager
            ->expects(self::once())
            ->method('synchronizeEntityToDraft')
            ->with($order, $orderDraft);

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        $this->listener->onOrderEvent($event);
    }
}
