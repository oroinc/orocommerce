<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Tests\Unit\EventListener\DraftSession;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\EventListener\DraftSession\SyncLineItemsOnOrderDraftCreatedEventListener;
use Oro\Component\DraftSession\Event\EntityDraftCreatedEvent;
use Oro\Component\DraftSession\Factory\EntityDraftFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SyncLineItemsOnOrderDraftCreatedEventListenerTest extends TestCase
{
    private EntityDraftFactoryInterface&MockObject $entityDraftFactory;

    private SyncLineItemsOnOrderDraftCreatedEventListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityDraftFactory = $this->createMock(EntityDraftFactoryInterface::class);

        $this->listener = new SyncLineItemsOnOrderDraftCreatedEventListener(
            $this->entityDraftFactory,
        );
    }

    public function testOnEntityDraftCreatedIgnoresWhenEntityIsNotRequest(): void
    {
        $event = $this->createMock(EntityDraftCreatedEvent::class);
        $event
            ->method('getEntity')
            ->willReturn(new Order());
        $event
            ->method('getDraft')
            ->willReturn(new Order());

        $this->entityDraftFactory
            ->expects(self::never())
            ->method('createDraft');

        $this->listener->onEntityDraftCreated($event);
    }

    public function testOnEntityDraftCreatedIgnoresWhenDraftIsNotOrder(): void
    {
        $event = $this->createMock(EntityDraftCreatedEvent::class);
        $event
            ->method('getEntity')
            ->willReturn(new Request());
        $event
            ->method('getDraft')
            ->willReturn(new OrderLineItem());

        $this->entityDraftFactory
            ->expects(self::never())
            ->method('createDraft');

        $this->listener->onEntityDraftCreated($event);
    }

    public function testOnEntityDraftCreatedDoesNothingWhenNoRequestProducts(): void
    {
        $request = new Request();
        $orderDraft = new Order();
        $orderDraft->setDraftSessionUuid('test-uuid');

        $event = $this->createMock(EntityDraftCreatedEvent::class);
        $event
            ->method('getEntity')
            ->willReturn($request);
        $event
            ->method('getDraft')
            ->willReturn($orderDraft);

        $this->entityDraftFactory
            ->expects(self::never())
            ->method('createDraft');

        $this->listener->onEntityDraftCreated($event);
    }

    public function testOnEntityDraftCreatedCreatesLineItemForEachRequestProduct(): void
    {
        $requestProduct1 = new RequestProduct();
        $requestProduct2 = new RequestProduct();

        $request = new Request();
        $request->addRequestProduct($requestProduct1);
        $request->addRequestProduct($requestProduct2);

        $orderDraft = new Order();
        $orderDraft->setDraftSessionUuid('test-uuid');

        $lineItem1 = new OrderLineItem();
        $lineItem2 = new OrderLineItem();

        $event = $this->createMock(EntityDraftCreatedEvent::class);
        $event
            ->method('getEntity')
            ->willReturn($request);
        $event
            ->method('getDraft')
            ->willReturn($orderDraft);

        $this->entityDraftFactory
            ->expects(self::exactly(2))
            ->method('createDraft')
            ->willReturnOnConsecutiveCalls($lineItem1, $lineItem2);

        $this->listener->onEntityDraftCreated($event);

        self::assertCount(2, $orderDraft->getLineItems());
        self::assertTrue($orderDraft->getLineItems()->contains($lineItem1));
        self::assertTrue($orderDraft->getLineItems()->contains($lineItem2));
    }
}
