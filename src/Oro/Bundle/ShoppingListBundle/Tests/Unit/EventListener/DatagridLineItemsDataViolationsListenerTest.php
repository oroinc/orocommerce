<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\EventListener\DatagridLineItemsDataViolationsListener;
use Oro\Bundle\ShoppingListBundle\Validator\LineItemViolationsProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Validator\ConstraintViolation;

class DatagridLineItemsDataViolationsListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var LineItemViolationsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $violationsProvider;

    /** @var DatagridLineItemsDataViolationsListener */
    private $listener;

    protected function setUp(): void
    {
        $this->violationsProvider = $this->createMock(LineItemViolationsProvider::class);
        $this->listener = new DatagridLineItemsDataViolationsListener($this->violationsProvider);
    }

    public function testOnLineItemDataWhenNoLineItems(): void
    {
        $event = $this->createMock(DatagridLineItemsDataEvent::class);

        $event
            ->expects($this->never())
            ->method('addDataForLineItem');

        $event
            ->expects($this->never())
            ->method('addDataForLineItem');

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemDataWhenNoViolations(): void
    {
        $event = $this->createMock(DatagridLineItemsDataEvent::class);
        $lineItems = [new LineItem(), new LineItem()];
        $event
            ->expects($this->once())
            ->method('getLineItems')
            ->willReturn($lineItems);

        $this->violationsProvider
            ->expects($this->once())
            ->method('getLineItemViolationLists')
            ->willReturn([]);

        $event
            ->expects($this->never())
            ->method('addDataForLineItem');

        $event
            ->expects($this->never())
            ->method('addDataForLineItem');

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemDataWhenViolations(): void
    {
        $productUnit = (new ProductUnit())->setCode('item');
        $product1 = (new Product())->setSku('sku1');
        $product2 = (new Product())->setSku('sku2');

        $lineItem1 = $this->getEntity(LineItem::class, ['id' => 11, 'product' => $product1, 'unit' => $productUnit]);
        $lineItem2 = $this->getEntity(LineItem::class, ['id' => 22, 'product' => $product2, 'unit' => $productUnit]);

        $lineItems = [
            $lineItem1->getEntityIdentifier() => $lineItem1,
            $lineItem2->getEntityIdentifier() => $lineItem2,
        ];
        $event = new DatagridLineItemsDataEvent(
            $lineItems,
            [],
            $this->createMock(DatagridInterface::class),
            []
        );

        $violation1 = $this->createMock(ConstraintViolation::class);
        $violation1->expects($this->once())
            ->method('getCause')
            ->willReturn('warning');
        $violation1->expects($this->once())
            ->method('getMessage')
            ->willReturn('warning_message1');

        $violation2 = $this->createMock(ConstraintViolation::class);
        $violation2->expects($this->once())
            ->method('getCause')
            ->willReturn('warning');
        $violation2->expects($this->once())
            ->method('getMessage')
            ->willReturn('warning_message2');

        $violation3 = $this->createMock(ConstraintViolation::class);
        $violation3->expects($this->once())
            ->method('getCause')
            ->willReturn('error');
        $violation3->expects($this->once())
            ->method('getMessage')
            ->willReturn('error_message3');

        $this->violationsProvider
            ->expects($this->once())
            ->method('getLineItemViolationLists')
            ->willReturn(['product.sku1.item' => [$violation1], 'product.sku2.item' => [$violation2, $violation3]]);

        $this->listener->onLineItemData($event);

        $this->assertEquals(['warnings' => ['warning_message1'], 'errors' => []], $event->getDataForLineItem(11));
        $this->assertEquals(
            ['warnings' => ['warning_message2'], 'errors' => ['error_message3']],
            $event->getDataForLineItem(22)
        );
    }
}
