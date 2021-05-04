<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\InventoryBundle\EventListener\UpcomingLabelCheckoutLineItemValidationListener;
use Oro\Bundle\InventoryBundle\Validator\UpcomingLabelCheckoutLineItemValidator;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Event\LineItemValidateEvent;

class UpcomingLabelCheckoutLineItemValidationListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var UpcomingLabelCheckoutLineItemValidationListener */
    protected $listener;

    /** @var UpcomingLabelCheckoutLineItemValidator|\PHPUnit\Framework\MockObject\MockObject */
    protected $validator;

    /** @var LineItemValidateEvent|\PHPUnit\Framework\MockObject\MockObject */
    protected $event;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(UpcomingLabelCheckoutLineItemValidator::class);
        $this->listener = new UpcomingLabelCheckoutLineItemValidationListener($this->validator);

        $this->event = $this->createMock(LineItemValidateEvent::class);
    }

    public function testOnLineItemValidate()
    {
        $product = $this->createMock(Product::class);
        $product->expects($this->once())->method('getSku')->willReturn('SKU');

        $lineItem = $this->createMock(CheckoutLineItem::class);
        $lineItem->expects($this->exactly(2))->method('getProduct')->willReturn($product);
        $lineItem->expects($this->exactly(1))->method('getProductUnitCode')->willReturn('set');

        $this->event->expects($this->once())->method('getLineItems')
            ->willReturn(new ArrayCollection([$lineItem]));

        $this->validator->expects($this->once())->method('getMessageIfLineItemUpcoming')->with($lineItem)
            ->willReturn('some string');

        $this->event->expects($this->once())->method('addWarningByUnit')->with('SKU', 'set', 'some string');

        $this->listener->onLineItemValidate($this->event);
    }

    public function testOnLineItemValidatewithNoTraversableObject()
    {
        $product = $this->createMock(Product::class);
        $product->expects($this->never())->method('getSku')->willReturn('SKU');

        $lineItem = $this->createMock(CheckoutLineItem::class);
        $lineItem->expects($this->never())->method('getProduct')->willReturn($product);
        $lineItem->expects($this->never())->method('getProductUnitCode')->willReturn('set');

        $this->event->expects($this->once())->method('getLineItems')->willReturn([$lineItem]);

        $this->validator->expects($this->never())->method('getMessageIfLineItemUpcoming')->with($lineItem)
            ->willReturn('some string');

        $this->event->expects($this->never())->method('addWarningByUnit')->with('SKU', 'set', 'some string');

        $this->listener->onLineItemValidate($this->event);
    }

    public function testOnLineItemValidatewithNoValidLineItems()
    {
        $product = $this->createMock(Product::class);
        $product->expects($this->never())->method('getSku')->willReturn('SKU');

        $this->event->expects($this->once())->method('getLineItems')->willReturn(['some string']);

        $this->validator->expects($this->never())->method('getMessageIfLineItemUpcoming');

        $this->event->expects($this->never())->method('addWarningByUnit')->with('SKU', 'set', 'some string');

        $this->listener->onLineItemValidate($this->event);
    }

    public function testOnLineItemValidatewithNoProducts()
    {
        $lineItem = $this->createMock(CheckoutLineItem::class);
        $lineItem->expects($this->once())->method('getProduct')->willReturn(null);

        $this->event->expects($this->once())->method('getLineItems')
            ->willReturn(new ArrayCollection([$lineItem]));

        $this->validator->expects($this->never())->method('getMessageIfLineItemUpcoming');

        $this->event->expects($this->never())->method('addWarningByUnit')->with('SKU', 'set', 'some string');

        $this->listener->onLineItemValidate($this->event);
    }
}
