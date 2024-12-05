<?php

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

class RecalculateOrdersOnSaveTest extends TestCase
{
    private MockObject|TotalHelper $totalHelper;
    private MockObject|PriceMatcher $priceMatcher;
    private MockObject|ManagerRegistry $doctrine;
    private RecalculateOrdersOnSave $recalculateOrdersOnSave;

    #[\Override]
    protected function setUp(): void
    {
        $this->totalHelper = $this->createMock(TotalHelper::class);
        $this->priceMatcher = $this->createMock(PriceMatcher::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->recalculateOrdersOnSave = new RecalculateOrdersOnSave(
            $this->totalHelper,
            $this->priceMatcher,
            $this->doctrine
        );
    }

    public function testOnBeforeFlushWithSubOrder(): void
    {
        $order = new Order();
        $subOrder = new Order();
        $order->addSubOrder($subOrder);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->priceMatcher->expects(self::once())
            ->method('addMatchingPrices')
            ->with($order);
        $this->totalHelper->expects(self::once())
            ->method('fill')
            ->with($order);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Order::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('persist')
            ->with($order);

        $this->recalculateOrdersOnSave->onBeforeFlush(new AfterFormProcessEvent(
            $this->createMock(FormInterface::class),
            $subOrder
        ));
    }

    public function testOnBeforeFlushWithMainOrder(): void
    {
        $order = new Order();
        $subOrder = new Order();
        $subOrder1 = new Order();
        $order->addSubOrder($subOrder);
        $order->addSubOrder($subOrder1);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->priceMatcher->expects(self::exactly(2))
            ->method('addMatchingPrices')
            ->withConsecutive([$subOrder], [$subOrder1]);
        $this->totalHelper->expects(self::exactly(2))
            ->method('fill')
            ->withConsecutive([$subOrder], [$subOrder1]);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Order::class)
            ->willReturn($em);
        $em->expects(self::exactly(2))
            ->method('persist')
            ->withConsecutive([$subOrder], [$subOrder1]);

        $this->recalculateOrdersOnSave->onBeforeFlush(new AfterFormProcessEvent(
            $this->createMock(FormInterface::class),
            $order
        ));
    }
}
