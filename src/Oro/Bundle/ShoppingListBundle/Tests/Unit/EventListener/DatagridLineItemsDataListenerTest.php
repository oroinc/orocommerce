<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\EventListener\DatagridLineItemsDataListener;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Stub\LineItemStub;

class DatagridLineItemsDataListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DatagridLineItemsDataListener */
    private $listener;

    protected function setUp(): void
    {
        $this->listener = new DatagridLineItemsDataListener();
    }

    public function testOnLineItemDataWhenNoLineItems(): void
    {
        $event = $this->createMock(DatagridLineItemsDataEvent::class);

        $event
            ->expects($this->never())
            ->method('addDataForLineItem');

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemDataWhenNotLineItem(): void
    {
        $event = $this->createMock(DatagridLineItemsDataEvent::class);

        $event
            ->expects($this->once())
            ->method('getLineItems')
            ->willReturn([new \stdClass()]);

        $event
            ->expects($this->never())
            ->method('addDataForLineItem');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('%s entity was expected, got stdClass', LineItem::class));

        $this->listener->onLineItemData($event);
    }

    /**
     * @dataProvider onLineItemDataDataProvider
     */
    public function testOnLineItemData(LineItem $lineItem, array $expectedArgs): void
    {
        $event = $this->createMock(DatagridLineItemsDataEvent::class);
        $event
            ->expects($this->once())
            ->method('getLineItems')
            ->willReturn([$lineItem]);

        $event
            ->expects($this->once())
            ->method('addDataForLineItem')
            ->with(...$expectedArgs);

        $this->listener->onLineItemData($event);
    }

    public function onLineItemDataDataProvider(): array
    {
        $lineItemId = 10;
        $notes = 'sample notes';

        return [
            [
                'lineItem' => (new LineItemStub())->setId($lineItemId)->setNotes($notes),
                'expectedArgs' => [$lineItemId, ['notes' => $notes]],
            ],
            [
                'lineItem' => (new LineItemStub())->setId($lineItemId),
                'expectedArgs' => [$lineItemId, ['notes' => '']],
            ],
        ];
    }
}
