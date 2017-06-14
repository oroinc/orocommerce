<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount;

use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountInformation;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;

class DiscountContextTest extends \PHPUnit_Framework_TestCase
{
    public function testSubtotal()
    {
        $amount = 4.2;
        $context = new DiscountContext();
        $context->setSubtotal($amount);
        $this->assertSame($amount, $context->getSubtotal());
    }

    public function testShippingCost()
    {
        $amount = 4.2;
        $context = new DiscountContext();
        $context->setShippingCost($amount);
        $this->assertSame($amount, $context->getShippingCost());
    }

    public function testLineItems()
    {
        $lineItems = [new DiscountLineItem()];
        $context = new DiscountContext();
        $context->setLineItems($lineItems);
        $this->assertSame($lineItems, $context->getLineItems());
    }

    public function testShippingDiscount()
    {
        /** @var DiscountInterface $discount */
        $discount = $this->createMock(DiscountInterface::class);
        $context = new DiscountContext();
        $context->addShippingDiscount($discount);
        $this->assertEquals([$discount], $context->getShippingDiscounts());
    }

    public function testShippingDiscountInformation()
    {
        /** @var DiscountInformation $info */
        $info = $this->createMock(DiscountInformation::class);
        $context = new DiscountContext();
        $context->addShippingDiscountInformation($info);
        $this->assertEquals([$info], $context->getShippingDiscountsInformation());
    }

    public function testSubtotalDiscount()
    {
        /** @var DiscountInterface $discount */
        $discount = $this->createMock(DiscountInterface::class);
        $context = new DiscountContext();
        $context->addSubtotalDiscount($discount);
        $this->assertEquals([$discount], $context->getSubtotalDiscounts());
    }

    public function testSubtotalDiscountInformation()
    {
        /** @var DiscountInformation $info */
        $info = $this->createMock(DiscountInformation::class);
        $context = new DiscountContext();
        $context->addSubtotalDiscountInformation($info);
        $this->assertEquals([$info], $context->getSubtotalDiscountsInformation());
    }
}
