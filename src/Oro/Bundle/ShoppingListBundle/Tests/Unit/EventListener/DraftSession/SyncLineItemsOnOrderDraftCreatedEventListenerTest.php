<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\EventListener\DraftSession;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\EventListener\DraftSession\SyncLineItemsOnOrderDraftCreatedEventListener;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
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

    public function testOnEntityDraftCreatedIgnoresWhenEntityIsNotShoppingList(): void
    {
        $event = new EntityDraftCreatedEvent(
            $this->createMock(EntityDraftAwareInterface::class),
            new Order()
        );

        $this->entityDraftFactory->expects(self::never())
            ->method('createDraft');

        $this->listener->onEntityDraftCreated($event);
    }

    public function testOnEntityDraftCreatedIgnoresWhenDraftIsNotOrder(): void
    {
        $event = new EntityDraftCreatedEvent(
            new ShoppingList(),
            $this->createMock(EntityDraftAwareInterface::class)
        );

        $this->entityDraftFactory->expects(self::never())
            ->method('createDraft');

        $this->listener->onEntityDraftCreated($event);
    }

    public function testOnEntityDraftCreatedCreatesDraftLineItems(): void
    {
        $draftSessionUuid = 'draft-session-uuid';

        $lineItem1 = new LineItem();
        $lineItem2 = new LineItem();

        $shoppingList = new ShoppingList();
        $shoppingList->addLineItem($lineItem1);
        $shoppingList->addLineItem($lineItem2);

        $orderLineItemDraft1 = new OrderLineItem();
        $orderLineItemDraft2 = new OrderLineItem();

        $orderDraft = new Order();
        $orderDraft->setDraftSessionUuid($draftSessionUuid);

        $event = new EntityDraftCreatedEvent($shoppingList, $orderDraft);

        $this->entityDraftFactory->expects(self::exactly(2))
            ->method('createDraft')
            ->withConsecutive(
                [$lineItem1, $draftSessionUuid],
                [$lineItem2, $draftSessionUuid]
            )
            ->willReturnOnConsecutiveCalls($orderLineItemDraft1, $orderLineItemDraft2);

        $this->listener->onEntityDraftCreated($event);

        self::assertCount(2, $orderDraft->getLineItems());
        self::assertTrue($orderDraft->getLineItems()->contains($orderLineItemDraft1));
        self::assertTrue($orderDraft->getLineItems()->contains($orderLineItemDraft2));
    }

    public function testOnEntityDraftCreatedWithEmptyLineItems(): void
    {
        $shoppingList = new ShoppingList();

        $orderDraft = new Order();

        $event = new EntityDraftCreatedEvent($shoppingList, $orderDraft);

        $this->entityDraftFactory->expects(self::never())
            ->method('createDraft');

        $this->listener->onEntityDraftCreated($event);

        self::assertCount(0, $orderDraft->getLineItems());
    }
}
