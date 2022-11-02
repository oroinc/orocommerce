<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountInformation;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DiscountContextTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

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
        $lineItem1 = new DiscountLineItem();
        $lineItem2 = new DiscountLineItem();
        $context = new DiscountContext();
        $context->setLineItems([$lineItem1]);
        $context->addLineItem($lineItem2);
        $this->assertSame([$lineItem1, $lineItem2], $context->getLineItems());
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

    public function testGetShippingDiscountTotal()
    {
        /** @var DiscountInterface $discount */
        $discount = $this->createMock(DiscountInterface::class);
        $discountInformation1 = new DiscountInformation($discount, 10.5);
        $discountInformation2 = new DiscountInformation($discount, 20);

        $context = new DiscountContext();
        $context->addShippingDiscountInformation($discountInformation1);
        $context->addShippingDiscountInformation($discountInformation2);

        $this->assertEquals(30.5, $context->getShippingDiscountTotal());
    }

    public function testGetSubtotalDiscountTotal()
    {
        /** @var DiscountInterface $discount */
        $discount = $this->createMock(DiscountInterface::class);
        $discountInformation1 = new DiscountInformation($discount, 10.5);
        $discountInformation2 = new DiscountInformation($discount, 20);

        $context = new DiscountContext();
        $context->addSubtotalDiscountInformation($discountInformation1);
        $context->addSubtotalDiscountInformation($discountInformation2);

        $this->assertEquals(30.5, $context->getSubtotalDiscountTotal());
    }

    public function testGetTotalLineItemsDiscount()
    {
        /** @var DiscountLineItem|\PHPUnit\Framework\MockObject\MockObject $lineItem1 */
        $lineItem1 = $this->createMock(DiscountLineItem::class);
        $lineItem1->expects($this->once())
            ->method('getDiscountTotal')
            ->willReturn(10.5);
        /** @var DiscountLineItem|\PHPUnit\Framework\MockObject\MockObject $lineItem2 */
        $lineItem2 = $this->createMock(DiscountLineItem::class);
        $lineItem2->expects($this->once())
            ->method('getDiscountTotal')
            ->willReturn(20.0);

        $context = new DiscountContext();
        $context->addLineItem($lineItem1);
        $context->addLineItem($lineItem2);

        $this->assertEquals(30.5, $context->getTotalLineItemsDiscount());
    }

    public function testGetDiscountByLineItem()
    {
        $lineItem1 = new OrderLineItem();
        $lineItemDiscount1 = new DiscountLineItem();
        $lineItemDiscount1->setSourceLineItem($lineItem1);
        $lineItemDiscount1->addDiscountInformation(
            new DiscountInformation($this->createMock(DiscountInterface::class), 11)
        );
        $lineItemDiscount1->addDiscountInformation(
            new DiscountInformation($this->createMock(DiscountInterface::class), 22)
        );

        $lineItem2 = new OrderLineItem();
        $lineItemDiscount2 = new DiscountLineItem();
        $lineItemDiscount2->setSourceLineItem($lineItem2);
        $lineItemDiscount2->addDiscountInformation(
            new DiscountInformation($this->createMock(DiscountInterface::class), 44)
        );
        $lineItemDiscount2->addDiscountInformation(
            new DiscountInformation($this->createMock(DiscountInterface::class), 55)
        );

        $context = new DiscountContext();
        $context->addLineItem($lineItemDiscount1);
        $context->addLineItem($lineItemDiscount2);

        $this->assertEquals(33, $context->getDiscountByLineItem($lineItem1));
        $this->assertEquals(99, $context->getDiscountByLineItem($lineItem2));
        $this->assertEquals(0, $context->getDiscountByLineItem(new OrderLineItem()));
        $this->assertEquals(0, $context->getDiscountByLineItem(new \stdClass()));
    }

    public function testGetLineItemDiscounts()
    {
        $discount1 = $this->createMock(DiscountInterface::class);
        $discount2 = $this->createMock(DiscountInterface::class);
        $lineItem1 = new DiscountLineItem();
        $lineItem1->addDiscount($discount1);
        $lineItem1->addDiscount($discount2);

        $lineItem2 = new DiscountLineItem();
        $lineItem2->addDiscount($discount1);

        $lineItem3 = new DiscountLineItem();

        $context = new DiscountContext();
        $context->setLineItems([$lineItem1, $lineItem2, $lineItem3]);

        $discounts = $context->getLineItemDiscounts();
        $this->assertCount(2, $discounts);
        $this->assertContains($discount1, $discounts);
        $this->assertContains($discount2, $discounts);
    }

    public function testCloneCreatesNewLineItemInstances()
    {
        $originalContext = new DiscountContext();
        $originalLineItem = new DiscountLineItem();
        $originalContext->addLineItem($originalLineItem);

        $clonedContext = clone $originalContext;

        $this->assertEquals($originalContext->getLineItems(), $clonedContext->getLineItems());
        $this->assertNotSame($originalContext->getLineItems(), $clonedContext->getLineItems());
    }
}
