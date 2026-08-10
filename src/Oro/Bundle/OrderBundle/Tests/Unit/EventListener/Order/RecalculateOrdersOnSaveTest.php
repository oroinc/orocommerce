<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\Order;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\EventListener\Order\RecalculateOrdersOnSave;
use Oro\Bundle\OrderBundle\Pricing\PriceMatcher;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;

final class RecalculateOrdersOnSaveTest extends TestCase
{
    private TotalHelper&MockObject $totalHelper;

    private PriceMatcher&MockObject $priceMatcher;

    private ManagerRegistry&MockObject $doctrine;

    private RecalculateOrdersOnSave $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->totalHelper = $this->createMock(TotalHelper::class);
        $this->priceMatcher = $this->createMock(PriceMatcher::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->listener = new RecalculateOrdersOnSave(
            $this->totalHelper,
            $this->priceMatcher,
            $this->doctrine
        );
    }

    public function testOnBeforeFlushDoesNothingWhenDataIsNotOrder(): void
    {
        $this->priceMatcher
            ->expects(self::never())
            ->method(self::anything());

        $this->totalHelper
            ->expects(self::never())
            ->method(self::anything());

        $this->doctrine
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->onBeforeFlush(
            new AfterFormProcessEvent($this->createMock(FormInterface::class), new \stdClass())
        );
    }

    public function testOnBeforeFlushRecalculatesOrderOnlyWhenNoParentAndNoSubOrders(): void
    {
        $order = new Order();

        $this->priceMatcher
            ->expects(self::once())
            ->method('addMatchingPrices')
            ->with(self::identicalTo($order));

        $this->totalHelper
            ->expects(self::once())
            ->method('fill')
            ->with(self::identicalTo($order));

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(Order::class)
            ->willReturn($entityManager);

        $entityManager
            ->expects(self::never())
            ->method('persist');

        $this->listener->onBeforeFlush(
            new AfterFormProcessEvent($this->createMock(FormInterface::class), $order)
        );
    }

    public function testOnBeforeFlushRecalculatesParentWhenOrderHasParent(): void
    {
        $parentOrder = new Order();
        $order = new Order();
        $parentOrder->addSubOrder($order);

        $this->priceMatcher
            ->expects(self::exactly(2))
            ->method('addMatchingPrices')
            ->withConsecutive([self::identicalTo($order)], [self::identicalTo($parentOrder)]);

        $this->totalHelper
            ->expects(self::exactly(2))
            ->method('fill')
            ->withConsecutive([self::identicalTo($order)], [self::identicalTo($parentOrder)]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(Order::class)
            ->willReturn($entityManager);

        $entityManager
            ->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($parentOrder));

        $this->listener->onBeforeFlush(
            new AfterFormProcessEvent($this->createMock(FormInterface::class), $order)
        );
    }

    public function testOnBeforeFlushRecalculatesSubOrdersWhenOrderHasSubOrders(): void
    {
        $order = new Order();
        $subOrder1 = new Order();
        $subOrder2 = new Order();
        $order->addSubOrder($subOrder1);
        $order->addSubOrder($subOrder2);

        $this->priceMatcher
            ->expects(self::exactly(3))
            ->method('addMatchingPrices')
            ->withConsecutive(
                [self::identicalTo($order)],
                [self::identicalTo($subOrder1)],
                [self::identicalTo($subOrder2)]
            );

        $this->totalHelper
            ->expects(self::exactly(3))
            ->method('fill')
            ->withConsecutive(
                [self::identicalTo($order)],
                [self::identicalTo($subOrder1)],
                [self::identicalTo($subOrder2)]
            );

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(Order::class)
            ->willReturn($entityManager);

        $entityManager
            ->expects(self::exactly(2))
            ->method('persist')
            ->withConsecutive([self::identicalTo($subOrder1)], [self::identicalTo($subOrder2)]);

        $this->listener->onBeforeFlush(
            new AfterFormProcessEvent($this->createMock(FormInterface::class), $order)
        );
    }

    public function testOnBeforeFlushRecalculatesParentOnlyWhenOrderHasBothParentAndSubOrders(): void
    {
        $parentOrder = new Order();
        $order = new Order();
        $subOrder = new Order();
        $parentOrder->addSubOrder($order);
        $order->addSubOrder($subOrder);

        $this->priceMatcher
            ->expects(self::exactly(2))
            ->method('addMatchingPrices')
            ->withConsecutive([self::identicalTo($order)], [self::identicalTo($parentOrder)]);

        $this->totalHelper
            ->expects(self::exactly(2))
            ->method('fill')
            ->withConsecutive([self::identicalTo($order)], [self::identicalTo($parentOrder)]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(Order::class)
            ->willReturn($entityManager);

        $entityManager
            ->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($parentOrder));

        $this->listener->onBeforeFlush(
            new AfterFormProcessEvent($this->createMock(FormInterface::class), $order)
        );
    }
}
