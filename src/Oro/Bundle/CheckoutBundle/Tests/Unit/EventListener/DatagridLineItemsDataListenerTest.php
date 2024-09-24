<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\EventListener\DatagridLineItemsDataListener;
use Oro\Bundle\CheckoutBundle\Tests\Unit\Stub\CheckoutLineItemStub;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use PHPUnit\Framework\TestCase;

class DatagridLineItemsDataListenerTest extends TestCase
{
    private DatagridLineItemsDataListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->listener = new DatagridLineItemsDataListener();
    }

    public function testOnLineItemDataWhenNoLineItems(): void
    {
        $event = $this->createMock(DatagridLineItemsDataEvent::class);
        $event->expects(self::never())
            ->method('addDataForLineItem');

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemDataWhenNotLineItem(): void
    {
        $event = $this->createMock(DatagridLineItemsDataEvent::class);
        $event->expects(self::once())
            ->method('getLineItems')
            ->willReturn([new \stdClass()]);
        $event->expects(self::never())
            ->method('addDataForLineItem');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('%s entity was expected, got stdClass', CheckoutLineItem::class));

        $this->listener->onLineItemData($event);
    }

    /**
     * @dataProvider onLineItemDataDataProvider
     */
    public function testOnLineItemData(CheckoutLineItem $lineItem, array $expectedArgs): void
    {
        $event = $this->createMock(DatagridLineItemsDataEvent::class);
        $event->expects(self::once())
            ->method('getLineItems')
            ->willReturn([$lineItem]);
        $event->expects(self::once())
            ->method('addDataForLineItem')
            ->with(...$expectedArgs);

        $this->listener->onLineItemData($event);
    }

    public function onLineItemDataDataProvider(): array
    {
        $lineItemId = 10;
        $comment = 'sample notes';

        return [
            [
                'lineItem' => (new CheckoutLineItemStub())->setId($lineItemId)->setComment($comment),
                'expectedArgs' => [$lineItemId, ['notes' => $comment, 'name' => null, 'sku' => null]],
            ],
            [
                'lineItem' => (new CheckoutLineItemStub())->setId($lineItemId)
                    ->setFreeFormProduct('Product 123')
                    ->setProductSku('sample-sku'),
                'expectedArgs' => [$lineItemId, ['notes' => '', 'name' => 'Product 123', 'sku' => 'sample-sku']],
            ],
        ];
    }
}
