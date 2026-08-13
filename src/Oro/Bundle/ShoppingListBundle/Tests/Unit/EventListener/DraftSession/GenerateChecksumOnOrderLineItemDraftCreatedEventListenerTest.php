<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\EventListener\DraftSession;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\EventListener\DraftSession\GenerateChecksumOnOrderLineItemDraftCreatedEventListener;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\Event\EntityDraftCreatedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GenerateChecksumOnOrderLineItemDraftCreatedEventListenerTest extends TestCase
{
    private LineItemChecksumGeneratorInterface&MockObject $lineItemChecksumGenerator;

    private GenerateChecksumOnOrderLineItemDraftCreatedEventListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->lineItemChecksumGenerator = $this->createMock(LineItemChecksumGeneratorInterface::class);

        $this->listener = new GenerateChecksumOnOrderLineItemDraftCreatedEventListener(
            $this->lineItemChecksumGenerator,
        );
    }

    public function testOnEntityDraftCreatedIgnoresWhenEntityIsNotShoppingList(): void
    {
        $event = new EntityDraftCreatedEvent(
            $this->createMock(EntityDraftAwareInterface::class),
            new Order()
        );

        $this->lineItemChecksumGenerator->expects(self::never())
            ->method('getChecksum');

        $this->listener->onEntityDraftCreated($event);
    }

    public function testOnEntityDraftCreatedIgnoresWhenDraftIsNotOrder(): void
    {
        $event = new EntityDraftCreatedEvent(
            new ShoppingList(),
            $this->createMock(EntityDraftAwareInterface::class)
        );

        $this->lineItemChecksumGenerator->expects(self::never())
            ->method('getChecksum');

        $this->listener->onEntityDraftCreated($event);
    }

    public function testOnEntityDraftCreatedSetsChecksumForEachLineItem(): void
    {
        $lineItem1 = new OrderLineItem();
        $lineItem2 = new OrderLineItem();

        $orderDraft = new Order();
        $orderDraft->addLineItem($lineItem1);
        $orderDraft->addLineItem($lineItem2);

        $event = new EntityDraftCreatedEvent(new ShoppingList(), $orderDraft);

        $this->lineItemChecksumGenerator->expects(self::exactly(2))
            ->method('getChecksum')
            ->willReturnMap([
                [$lineItem1, 'checksum-1'],
                [$lineItem2, 'checksum-2'],
            ]);

        $this->listener->onEntityDraftCreated($event);

        self::assertSame('checksum-1', $lineItem1->getChecksum());
        self::assertSame('checksum-2', $lineItem2->getChecksum());
    }

    public function testOnEntityDraftCreatedSetsEmptyChecksumWhenGeneratorReturnsNull(): void
    {
        $lineItem = new OrderLineItem();

        $orderDraft = new Order();
        $orderDraft->addLineItem($lineItem);

        $event = new EntityDraftCreatedEvent(new ShoppingList(), $orderDraft);

        $this->lineItemChecksumGenerator->expects(self::once())
            ->method('getChecksum')
            ->with($lineItem)
            ->willReturn(null);

        $this->listener->onEntityDraftCreated($event);

        self::assertSame('', $lineItem->getChecksum());
    }

    public function testOnEntityDraftCreatedWithEmptyLineItems(): void
    {
        $orderDraft = new Order();

        $event = new EntityDraftCreatedEvent(new ShoppingList(), $orderDraft);

        $this->lineItemChecksumGenerator->expects(self::never())
            ->method('getChecksum');

        $this->listener->onEntityDraftCreated($event);
    }
}
