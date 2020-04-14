<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount;

use Oro\Bundle\PromotionBundle\Discount\DisabledDiscountContextDecorator;
use Oro\Bundle\PromotionBundle\Discount\DisabledDiscountDecorator;
use Oro\Bundle\PromotionBundle\Discount\DisabledDiscountLineItemDecorator;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountInformation;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DisabledDiscountContextDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DiscountContext|\PHPUnit\Framework\MockObject\MockObject
     */
    private $discountContext;

    /**
     * @var DisabledDiscountContextDecorator
     */
    private $decorator;

    protected function setUp(): void
    {
        $this->discountContext = $this->createMock(DiscountContext::class);
        $this->decorator = new DisabledDiscountContextDecorator($this->discountContext);
    }

    public function testGetSubtotal()
    {
        $subtotal = 111.0;
        $this->discountContext
            ->expects($this->once())
            ->method('getSubtotal')
            ->willReturn($subtotal);

        static::assertEquals($subtotal, $this->decorator->getSubtotal());
    }

    public function testSetSubtotal()
    {
        $subtotal = 111;
        $this->discountContext
            ->expects($this->once())
            ->method('setSubtotal')
            ->with($subtotal);

        $this->decorator->setSubtotal($subtotal);
    }

    public function testAddShippingDiscount()
    {
        /** @var DiscountInterface|\PHPUnit\Framework\MockObject\MockObject $discount **/
        $discount = $this->createMock(DiscountInterface::class);

        $this->discountContext
            ->expects($this->once())
            ->method('addShippingDiscount')
            ->with(new DisabledDiscountDecorator($discount));

        $this->decorator->addShippingDiscount($discount);
    }

    public function testAddSubtotalDiscount()
    {
        /** @var DiscountInterface|\PHPUnit\Framework\MockObject\MockObject $discount **/
        $discount = $this->createMock(DiscountInterface::class);

        $this->discountContext
            ->expects($this->once())
            ->method('addSubtotalDiscount')
            ->with(new DisabledDiscountDecorator($discount));

        $this->decorator->addSubtotalDiscount($discount);
    }

    public function testGetLineItems()
    {
        $lineItem = new DiscountLineItem();
        $this->discountContext
            ->expects($this->once())
            ->method('getLineItems')
            ->willReturn([$lineItem]);

        static::assertEquals([new DisabledDiscountLineItemDecorator($lineItem)], $this->decorator->getLineItems());
    }

    public function testSetLineItems()
    {
        $lineItems = [new DiscountLineItem()];
        $this->discountContext
            ->expects($this->once())
            ->method('setLineItems')
            ->with($lineItems);

        $this->decorator->setLineItems($lineItems);
    }

    public function testAddLineItem()
    {
        $lineItem = new DiscountLineItem();
        $this->discountContext
            ->expects($this->once())
            ->method('addLineItem')
            ->with($lineItem);

        $this->decorator->addLineItem($lineItem);
    }

    public function testGetShippingDiscounts()
    {
        $discounts = [$this->createMock(DiscountInterface::class)];
        $this->discountContext
            ->expects($this->once())
            ->method('getShippingDiscounts')
            ->willReturn($discounts);

        static::assertEquals($discounts, $this->decorator->getShippingDiscounts());
    }

    public function testGetSubtotalDiscounts()
    {
        $discounts = [$this->createMock(DiscountInterface::class)];
        $this->discountContext
            ->expects($this->once())
            ->method('getSubtotalDiscounts')
            ->willReturn($discounts);

        static::assertEquals($discounts, $this->decorator->getSubtotalDiscounts());
    }

    public function testGetLineItemDiscounts()
    {
        $discounts = [$this->createMock(DiscountInterface::class)];
        $this->discountContext
            ->expects($this->once())
            ->method('getLineItemDiscounts')
            ->willReturn($discounts);

        static::assertEquals($discounts, $this->decorator->getLineItemDiscounts());
    }

    public function testGetShippingCost()
    {
        $shippingCost = 0.01;
        $this->discountContext
            ->expects($this->once())
            ->method('getShippingCost')
            ->willReturn($shippingCost);

        static::assertEquals($shippingCost, $this->decorator->getShippingCost());
    }

    public function testSetShippingCost()
    {
        $shippingCost = 0.01;
        $this->discountContext
            ->expects($this->once())
            ->method('setShippingCost')
            ->with($shippingCost);

        $this->decorator->setShippingCost($shippingCost);
    }

    public function testAddSubtotalDiscountInformation()
    {
        /** @var DiscountInformation|\PHPUnit\Framework\MockObject\MockObject $discountInformation **/
        $discountInformation = $this->createMock(DiscountInformation::class);
        $this->discountContext
            ->expects($this->once())
            ->method('addSubtotalDiscountInformation')
            ->with($discountInformation);

        $this->decorator->addSubtotalDiscountInformation($discountInformation);
    }

    public function testGetSubtotalDiscountsInformation()
    {
        /** @var DiscountInformation|\PHPUnit\Framework\MockObject\MockObject $discountInformation **/
        $discountInformation = $this->createMock(DiscountInformation::class);
        $this->discountContext
            ->expects($this->once())
            ->method('getSubtotalDiscountsInformation')
            ->willReturn([$discountInformation]);

        static::assertEquals([$discountInformation], $this->decorator->getSubtotalDiscountsInformation());
    }

    public function testAddShippingDiscountInformation()
    {
        /** @var DiscountInformation|\PHPUnit\Framework\MockObject\MockObject $discountInformation **/
        $discountInformation = $this->createMock(DiscountInformation::class);
        $this->discountContext
            ->expects($this->once())
            ->method('addShippingDiscountInformation')
            ->with($discountInformation);

        $this->decorator->addShippingDiscountInformation($discountInformation);
    }

    public function testGetShippingDiscountsInformation()
    {
        /** @var DiscountInformation|\PHPUnit\Framework\MockObject\MockObject $discountInformation **/
        $discountInformation = $this->createMock(DiscountInformation::class);
        $this->discountContext
            ->expects($this->once())
            ->method('getShippingDiscountsInformation')
            ->willReturn([$discountInformation]);

        static::assertEquals([$discountInformation], $this->decorator->getShippingDiscountsInformation());
    }

    public function testGetShippingDiscountTotal()
    {
        $total = 1.3;
        $this->discountContext
            ->expects($this->once())
            ->method('getShippingDiscountTotal')
            ->willReturn($total);

        static::assertEquals($total, $this->decorator->getShippingDiscountTotal());
    }

    public function testGetSubtotalDiscountTotal()
    {
        $total = 1.3;
        $this->discountContext
            ->expects($this->once())
            ->method('getSubtotalDiscountTotal')
            ->willReturn($total);

        static::assertEquals($total, $this->decorator->getSubtotalDiscountTotal());
    }

    public function testGetTotalLineItemsDiscount()
    {
        $total = 1.3;
        $this->discountContext
            ->expects($this->once())
            ->method('getTotalLineItemsDiscount')
            ->willReturn($total);

        static::assertEquals($total, $this->decorator->getTotalLineItemsDiscount());
    }

    public function testGetDiscountByLineItem()
    {
        $total = 1.3;
        $lineItem = new LineItem();

        $this->discountContext
            ->expects($this->once())
            ->method('getDiscountByLineItem')
            ->with($lineItem)
            ->willReturn($total);

        static::assertEquals($total, $this->decorator->getDiscountByLineItem($lineItem));
    }

    public function testGetTotalDiscountAmount()
    {
        $total = 1.3;
        $this->discountContext
            ->expects($this->once())
            ->method('getTotalDiscountAmount')
            ->willReturn($total);

        static::assertEquals($total, $this->decorator->getTotalDiscountAmount());
    }
}
