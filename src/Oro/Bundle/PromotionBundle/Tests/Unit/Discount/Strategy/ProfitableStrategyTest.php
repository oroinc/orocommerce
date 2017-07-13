<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount\Strategy;

use Oro\Bundle\PromotionBundle\Discount\AbstractDiscount;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Discount\OrderDiscount;
use Oro\Bundle\PromotionBundle\Discount\Strategy\ProfitableStrategy;

class ProfitableStrategyTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $discount1 = $this->createOrderDiscount(10);
        $discount2 = $this->createOrderDiscount(20);

        $discountContext = new DiscountContext();
        $discountContext->setLineItems(
            [
                (new DiscountLineItem())->setSubtotal(100),
                (new DiscountLineItem())->setSubtotal(200)
            ]
        );
        $discountContext->setSubtotal(300);
        $discountContext->setShippingCost(10);

        $strategy = new ProfitableStrategy();
        $processedContext = $strategy->process($discountContext, [$discount1, $discount2]);

        $this->assertInstanceOf(DiscountContext::class, $processedContext);
        $appliedDiscounts = [];
        foreach ($processedContext->getLineItems() as $lineItem) {
            $appliedDiscounts = array_merge($appliedDiscounts, $lineItem->getDiscounts());
        }
        $appliedDiscounts = array_merge($appliedDiscounts, $processedContext->getSubtotalDiscounts());
        $appliedDiscounts = array_merge($appliedDiscounts, $processedContext->getShippingDiscounts());

        $this->assertNotEmpty($appliedDiscounts);
        $this->assertContains($discount2, $appliedDiscounts);
        $this->assertNotContains($discount1, $appliedDiscounts);
        $this->assertEquals(280, $processedContext->getSubtotal());
    }

    /**
     * @param float $amount
     * @return OrderDiscount
     */
    private function createOrderDiscount($amount): OrderDiscount
    {
        /** @var DiscountInterface $shippingDiscount */
        $shippingDiscount = $this->createMock(DiscountInterface::class);
        $discount = new OrderDiscount($shippingDiscount);
        $discount->configure([
            AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
            AbstractDiscount::DISCOUNT_VALUE => $amount,
            AbstractDiscount::DISCOUNT_CURRENCY => 'USD',
        ]);

        return $discount;
    }

    public function testGetLabel()
    {
        $strategy = new ProfitableStrategy();
        $this->assertEquals('oro.promotion.discount.strategy.profitable.label', $strategy->getLabel());
    }
}
