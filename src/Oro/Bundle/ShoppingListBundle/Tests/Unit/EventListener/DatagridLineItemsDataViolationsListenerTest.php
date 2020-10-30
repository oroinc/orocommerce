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
use Symfony\Component\Validator\ConstraintViolationInterface;

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

        $event = new DatagridLineItemsDataEvent(
            [$lineItem1, $lineItem2],
            $this->createMock(DatagridInterface::class),
            []
        );

        $violation1 = $this->createMock(ConstraintViolationInterface::class);
        $violation1
            ->expects($this->once())
            ->method('getMessage')
            ->willReturn('error_message1');

        $violation2 = $this->createMock(ConstraintViolationInterface::class);
        $violation2
            ->expects($this->once())
            ->method('getMessage')
            ->willReturn('error_message2');

        $this->violationsProvider
            ->expects($this->once())
            ->method('getLineItemViolationLists')
            ->willReturn(['product.sku1.item' => [$violation1], 'product.sku2.item' => [$violation2]]);

        $this->listener->onLineItemData($event);

        $this->assertEquals(['errors' => ['error_message1']], $event->getDataForLineItem(11));
        $this->assertEquals(['errors' => ['error_message2']], $event->getDataForLineItem(22));
    }
}
