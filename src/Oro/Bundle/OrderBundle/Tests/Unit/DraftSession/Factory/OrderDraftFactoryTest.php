<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\DraftSession\Factory;

use Oro\Bundle\OrderBundle\DraftSession\Factory\OrderDraftFactory;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Component\DraftSession\Factory\EntityDraftFactoryInterface;
use Oro\Component\DraftSession\Synchronizer\EntityDraftSynchronizerInterface;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OrderDraftFactoryTest extends TestCase
{
    private EntityDraftSynchronizerInterface&MockObject $entityDraftSynchronizer;
    private EntityDraftFactoryInterface&MockObject $entityDraftFactory;
    private OrderDraftFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityDraftSynchronizer = $this->createMock(EntityDraftSynchronizerInterface::class);
        $this->entityDraftFactory = $this->createMock(EntityDraftFactoryInterface::class);

        $this->factory = new OrderDraftFactory($this->entityDraftSynchronizer, $this->entityDraftFactory);
    }

    public function testSupportsReturnsTrue(): void
    {
        self::assertTrue($this->factory->supports(Order::class));
    }

    public function testSupportsReturnsFalse(): void
    {
        self::assertFalse($this->factory->supports(OrderLineItem::class));
        self::assertFalse($this->factory->supports(\stdClass::class));
    }

    public function testCreateDraftCreatesNewOrderDraft(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, 100);

        $this->entityDraftSynchronizer
            ->expects(self::once())
            ->method('synchronizeToDraft')
            ->willReturnCallback(function ($source, $target) use ($order) {
                self::assertSame($order, $source);
                self::assertInstanceOf(Order::class, $target);
                self::assertNotSame($order, $target);
            });

        $orderDraft = $this->factory->createDraft($order, 'uuid-123');

        self::assertNotSame($order, $orderDraft);
    }

    public function testCreateDraftSetsDraftSessionUuid(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, 100);

        $this->entityDraftSynchronizer
            ->expects(self::once())
            ->method('synchronizeToDraft');

        $orderDraft = $this->factory->createDraft($order, 'test-uuid-456');

        self::assertEquals('test-uuid-456', $orderDraft->getDraftSessionUuid());
    }

    public function testCreateDraftSetsDraftSourceToOrderWhenOrderHasId(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, 200);

        $this->entityDraftSynchronizer
            ->expects(self::once())
            ->method('synchronizeToDraft');

        $orderDraft = $this->factory->createDraft($order, 'uuid-789');

        self::assertSame($order, $orderDraft->getDraftSource());
    }

    public function testCreateDraftSetsDraftSourceToNullWhenOrderHasNoId(): void
    {
        $order = new Order();

        $this->entityDraftSynchronizer
            ->expects(self::once())
            ->method('synchronizeToDraft');

        self::assertNull($order->getId());

        $orderDraft = $this->factory->createDraft($order, 'uuid-abc');

        self::assertNull($orderDraft->getDraftSource());
    }

    public function testCreateDraftCallsSynchronizeToDraft(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, 300);

        $this->entityDraftSynchronizer
            ->expects(self::once())
            ->method('synchronizeToDraft')
            ->with(
                self::identicalTo($order),
                self::isInstanceOf(Order::class)
            );

        $this->factory->createDraft($order, 'uuid-def');
    }

    public function testCreateDraftSynchronizesLineItemsForNewOrder(): void
    {
        $order = new Order();
        $lineItem1 = new OrderLineItem();
        $lineItem2 = new OrderLineItem();
        $order->addLineItem($lineItem1);
        $order->addLineItem($lineItem2);

        $lineItemDraft1 = new OrderLineItem();
        $lineItemDraft2 = new OrderLineItem();

        $draftSessionUuid = 'uuid-new-order';

        $this->entityDraftFactory
            ->expects(self::exactly(2))
            ->method('createDraft')
            ->willReturnOnConsecutiveCalls($lineItemDraft1, $lineItemDraft2);

        $this->entityDraftSynchronizer
            ->expects(self::once())
            ->method('synchronizeToDraft');

        $orderDraft = $this->factory->createDraft($order, $draftSessionUuid);

        self::assertCount(2, $orderDraft->getLineItems());
        self::assertTrue($orderDraft->getLineItems()->contains($lineItemDraft1));
        self::assertTrue($orderDraft->getLineItems()->contains($lineItemDraft2));
    }

    public function testCreateDraftDoesNotSynchronizeLineItemsForExistingOrder(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, 500);
        $lineItem = new OrderLineItem();
        $order->addLineItem($lineItem);

        $this->entityDraftFactory
            ->expects(self::never())
            ->method('createDraft');

        $this->entityDraftSynchronizer
            ->expects(self::once())
            ->method('synchronizeToDraft');

        $orderDraft = $this->factory->createDraft($order, 'uuid-existing');

        self::assertCount(0, $orderDraft->getLineItems());
    }
}
