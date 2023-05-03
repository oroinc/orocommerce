<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\EventListener\DatagridLineItemsDataListener;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Stub\LineItemStub;
use PHPUnit\Framework\TestCase;

class DatagridLineItemsDataListenerTest extends TestCase
{
    private DatagridLineItemsDataListener $listener;

    protected function setUp(): void
    {
        $this->listener = new DatagridLineItemsDataListener();
    }

    public function testOnLineItemDataWhenNoLineItems(): void
    {
        $event = $this->createMock(DatagridLineItemsDataEvent::class);

        $event
            ->expects(self::never())
            ->method('addDataForLineItem');

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemDataWhenNotLineItem(): void
    {
        $event = $this->createMock(DatagridLineItemsDataEvent::class);

        $event
            ->expects(self::once())
            ->method('getLineItems')
            ->willReturn([new \stdClass()]);

        $event
            ->expects(self::never())
            ->method('addDataForLineItem');


        $this->listener->onLineItemData($event);
    }

    /**
     * @dataProvider onLineItemDataDataProvider
     */
    public function testOnLineItemData(LineItem $lineItem, array $expectedArgs): void
    {
        $event = $this->createMock(DatagridLineItemsDataEvent::class);
        $event
            ->expects(self::once())
            ->method('getLineItems')
            ->willReturn([$lineItem]);

        $event
            ->expects(self::once())
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
