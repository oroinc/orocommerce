<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\DraftSession;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\EventListener\DraftSession\OrderDraftBeforeEntityFlushListener;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OrderDraftBeforeEntityFlushListenerTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private OrderDraftManager&MockObject $orderDraftManager;
    private EntityManagerInterface&MockObject $entityManager;
    private OrderDraftBeforeEntityFlushListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->orderDraftManager = $this->createMock(OrderDraftManager::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->listener = new OrderDraftBeforeEntityFlushListener(
            $this->doctrine,
            $this->orderDraftManager
        );
    }

    public function testOnBeforeEntityFlushWhenDataIsNotOrder(): void
    {
        $event = $this->createMock(AfterFormProcessEvent::class);

        $event
            ->expects(self::once())
            ->method('getData')
            ->willReturn(new \stdClass());

        $this->orderDraftManager
            ->expects(self::never())
            ->method('getOrderDraft');

        $this->listener->onBeforeEntityFlush($event);
    }

    public function testOnBeforeEntityFlushWhenDataIsNull(): void
    {
        $event = $this->createMock(AfterFormProcessEvent::class);

        $event
            ->expects(self::once())
            ->method('getData')
            ->willReturn(null);

        $this->orderDraftManager
            ->expects(self::never())
            ->method('getOrderDraft');

        $this->listener->onBeforeEntityFlush($event);
    }

    public function testOnBeforeEntityFlushWhenOrderDraftNotFound(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, 100);

        $event = $this->createMock(AfterFormProcessEvent::class);

        $event
            ->expects(self::once())
            ->method('getData')
            ->willReturn($order);

        $this->orderDraftManager
            ->expects(self::once())
            ->method('getOrderDraft')
            ->willReturn(null);

        $this->doctrine
            ->expects(self::never())
            ->method('getManagerForClass');

        $this->listener->onBeforeEntityFlush($event);
    }

    public function testOnBeforeEntityFlushWhenOrderDraftFound(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, 200);

        $orderDraft = new Order();
        ReflectionUtil::setId($orderDraft, 300);

        $event = $this->createMock(AfterFormProcessEvent::class);

        $event
            ->expects(self::once())
            ->method('getData')
            ->willReturn($order);

        $this->orderDraftManager
            ->expects(self::once())
            ->method('getOrderDraft')
            ->willReturn($orderDraft);

        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(Order::class)
            ->willReturn($this->entityManager);

        $this->entityManager
            ->expects(self::once())
            ->method('remove')
            ->with($orderDraft);

        $this->listener->onBeforeEntityFlush($event);
    }
}
