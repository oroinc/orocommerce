<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\DraftSession\Factory;

use Oro\Bundle\OrderBundle\DraftSession\Factory\OrderLineItemDraftFactory;
use Oro\Bundle\OrderBundle\DraftSession\OrderLineItemDraftSynchronizer;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Component\DraftSession\Provider\EntityDraftRepositoryInterface;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OrderLineItemDraftFactoryTest extends TestCase
{
    private OrderLineItemDraftSynchronizer&MockObject $entityDraftSynchronizer;
    private OrderLineItemDraftFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityDraftSynchronizer = $this->createMock(OrderLineItemDraftSynchronizer::class);

        $this->factory = new OrderLineItemDraftFactory($this->entityDraftSynchronizer);
    }

    public function testCreateDraftCreatesNewOrderLineItemDraft(): void
    {
        $lineItem = new OrderLineItem();
        ReflectionUtil::setId($lineItem, 100);

        $this->entityDraftSynchronizer->expects(self::once())
            ->method('synchronizeToDraft');

        $lineItemDraft = $this->factory->createDraft($lineItem, 'uuid-123');

        self::assertNotSame($lineItem, $lineItemDraft);
    }

    public function testCreateDraftSetsDraftSessionUuid(): void
    {
        $lineItem = new OrderLineItem();
        ReflectionUtil::setId($lineItem, 100);

        $this->entityDraftSynchronizer->expects(self::once())
            ->method('synchronizeToDraft');

        $lineItemDraft = $this->factory->createDraft($lineItem, 'test-uuid-456');

        self::assertSame('test-uuid-456', $lineItemDraft->getDraftSessionUuid());
    }

    public function testCreateDraftSetsDraftSourceToLineItemWhenLineItemHasId(): void
    {
        $lineItem = new OrderLineItem();
        ReflectionUtil::setId($lineItem, 200);

        $this->entityDraftSynchronizer->expects(self::once())
            ->method('synchronizeToDraft');

        $lineItemDraft = $this->factory->createDraft($lineItem, 'uuid-789');

        self::assertSame($lineItem, $lineItemDraft->getDraftSource());
    }

    public function testCreateDraftSetsDraftSourceToLineItemWhenLineItemHasNoId(): void
    {
        $lineItem = new OrderLineItem();

        $this->entityDraftSynchronizer->expects(self::once())
            ->method('synchronizeToDraft');

        self::assertNull($lineItem->getId());

        $lineItemDraft = $this->factory->createDraft($lineItem, 'uuid-abc');

        self::assertSame($lineItem, $lineItemDraft->getDraftSource());
    }

    public function testCreateDraftCallsSynchronizeToDraft(): void
    {
        $lineItem = new OrderLineItem();
        ReflectionUtil::setId($lineItem, 300);

        $this->entityDraftSynchronizer->expects(self::once())
            ->method('synchronizeToDraft')
            ->with(
                self::identicalTo($lineItem),
                self::isInstanceOf(OrderLineItem::class)
            );

        $this->factory->createDraft($lineItem, 'uuid-def');
    }

    public function testCreateDraftAddsLineItemDraftToExistingOrderDraft(): void
    {
        $draftSessionUuid = 'uuid-order-existing';
        $order = new Order();
        ReflectionUtil::setId($order, 10);

        $orderDraft = new Order();
        ReflectionUtil::setId($orderDraft, 20);

        $lineItem = new OrderLineItem();
        $order->addLineItem($lineItem);

        $orderDraftRepository = $this->createMock(EntityDraftRepositoryInterface::class);
        $orderDraftRepository
            ->expects(self::once())
            ->method('findEntityDraft')
            ->with($order, $draftSessionUuid)
            ->willReturn($orderDraft);
        $this->factory->setOrderDraftRepository($orderDraftRepository);

        $this->entityDraftSynchronizer
            ->expects(self::once())
            ->method('synchronizeToDraft');

        $lineItemDraft = $this->factory->createDraft($lineItem, $draftSessionUuid);

        self::assertTrue($orderDraft->getLineItems()->contains($lineItemDraft));
    }

    public function testCreateDraftAddsLineItemDraftToNewOrderDraftReference(): void
    {
        $draftSessionUuid = 'uuid-order-new';
        $order = new Order();
        $orderDraft = new Order();
        ReflectionUtil::setId($orderDraft, 200);
        $order->addDraft($orderDraft);

        $lineItem = new OrderLineItem();
        $order->addLineItem($lineItem);

        $orderDraftRepository = $this->createMock(EntityDraftRepositoryInterface::class);
        $orderDraftRepository
            ->expects(self::never())
            ->method('findEntityDraft');
        $this->factory->setOrderDraftRepository($orderDraftRepository);

        $this->entityDraftSynchronizer
            ->expects(self::once())
            ->method('synchronizeToDraft');

        $lineItemDraft = $this->factory->createDraft($lineItem, $draftSessionUuid);

        self::assertTrue($orderDraft->getLineItems()->contains($lineItemDraft));
    }

    public function testCreateDraftWhenNoOrderDraftFoundForExistingOrder(): void
    {
        $draftSessionUuid = 'uuid-no-draft';
        $order = new Order();
        ReflectionUtil::setId($order, 10);

        $lineItem = new OrderLineItem();
        $order->addLineItem($lineItem);

        $orderDraftRepository = $this->createMock(EntityDraftRepositoryInterface::class);
        $orderDraftRepository
            ->expects(self::once())
            ->method('findEntityDraft')
            ->with($order, $draftSessionUuid)
            ->willReturn(null);
        $this->factory->setOrderDraftRepository($orderDraftRepository);

        $this->entityDraftSynchronizer
            ->expects(self::once())
            ->method('synchronizeToDraft');

        $lineItemDraft = $this->factory->createDraft($lineItem, $draftSessionUuid);

        self::assertNotNull($lineItemDraft);
    }

    public function testCreateDraftWhenNoOrderDraftReferenceForNewOrder(): void
    {
        $draftSessionUuid = 'uuid-no-reference';
        $order = new Order();

        $lineItem = new OrderLineItem();
        $order->addLineItem($lineItem);

        $orderDraftRepository = $this->createMock(EntityDraftRepositoryInterface::class);
        $orderDraftRepository
            ->expects(self::never())
            ->method('findEntityDraft');
        $this->factory->setOrderDraftRepository($orderDraftRepository);

        $this->entityDraftSynchronizer
            ->expects(self::once())
            ->method('synchronizeToDraft');

        $lineItemDraft = $this->factory->createDraft($lineItem, $draftSessionUuid);

        self::assertNotNull($lineItemDraft);
    }

    public function testCreateDraftWhenLineItemHasNoOrder(): void
    {
        $draftSessionUuid = 'uuid-no-order';
        $lineItem = new OrderLineItem();

        $orderDraftRepository = $this->createMock(EntityDraftRepositoryInterface::class);
        $orderDraftRepository
            ->expects(self::never())
            ->method('findEntityDraft');
        $this->factory->setOrderDraftRepository($orderDraftRepository);

        $this->entityDraftSynchronizer
            ->expects(self::once())
            ->method('synchronizeToDraft');

        $lineItemDraft = $this->factory->createDraft($lineItem, $draftSessionUuid);

        self::assertNotNull($lineItemDraft);
    }
}
