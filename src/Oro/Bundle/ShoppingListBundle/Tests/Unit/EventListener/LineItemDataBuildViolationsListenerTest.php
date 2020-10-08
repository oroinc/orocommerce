<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Event\LineItemDataBuildEvent;
use Oro\Bundle\ShoppingListBundle\EventListener\LineItemDataBuildViolationsListener;
use Oro\Bundle\ShoppingListBundle\Validator\LineItemViolationsProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Validator\ConstraintViolationInterface;

class LineItemDataBuildViolationsListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var LineItemViolationsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $violationsProvider;

    /** @var LineItemDataBuildViolationsListener */
    private $listener;

    protected function setUp(): void
    {
        $this->violationsProvider = $this->createMock(LineItemViolationsProvider::class);
        $this->listener = new LineItemDataBuildViolationsListener($this->violationsProvider);
    }

    public function testOnLineItemDataWhenNoLineItems(): void
    {
        $event = $this->createMock(LineItemDataBuildEvent::class);

        $event
            ->expects($this->never())
            ->method('setDataForLineItem');

        $event
            ->expects($this->never())
            ->method('addDataForLineItem');

        $this->listener->onLineItemData($event);
    }


    public function testOnLineItemDataWhenNoViolations(): void
    {
        $event = $this->createMock(LineItemDataBuildEvent::class);
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
            ->method('setDataForLineItem');

        $event
            ->expects($this->never())
            ->method('addDataForLineItem');

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemDataWhenViolations(): void
    {
        $event = $this->createMock(LineItemDataBuildEvent::class);
        $productUnit = (new ProductUnit())->setCode('item');
        $product1 = (new Product())->setSku('sku1');
        $product2 = (new Product())->setSku('sku2');

        $lineItem1 = $this->getEntity(LineItem::class, ['id' => 11, 'product' => $product1, 'unit' => $productUnit]);
        $lineItem2 = $this->getEntity(LineItem::class, ['id' => 22, 'product' => $product2, 'unit' => $productUnit]);
        $lineItems = [$lineItem1, $lineItem2];
        $event
            ->expects($this->once())
            ->method('getLineItems')
            ->willReturn($lineItems);

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

        $event
            ->expects($this->exactly(2))
            ->method('addDataForLineItem')
            ->withConsecutive(
                [11, 'errors', ['error_message1']],
                [22, 'errors', ['error_message2']]
            );

        $this->listener->onLineItemData($event);
    }
}
