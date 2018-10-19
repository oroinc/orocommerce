<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount\Strategy;

use Oro\Bundle\PromotionBundle\Discount\AbstractDiscount;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Discount\OrderDiscount;
use Oro\Bundle\PromotionBundle\Discount\ShippingDiscount;
use Oro\Bundle\PromotionBundle\Discount\Strategy\ProfitableStrategy;

class ProfitableStrategyTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess()
    {
        $discount1 = $this->createOrderDiscount(10);
        $discount2 = $this->createOrderDiscount(20);

        $shippingDiscount1 = $this->createShippingDiscount(15);
        $shippingDiscount2 = $this->createShippingDiscount(5);

        $discountContext = new DiscountContext();
        $discountContext->setLineItems(
            [
                (new DiscountLineItem())->setSubtotal(100),
                (new DiscountLineItem())->setSubtotal(200)
            ]
        );
        $discountContext->setSubtotal(300);
        $discountContext->setShippingCost(80);

        $strategy = new ProfitableStrategy();
        $processedContext = $strategy->process(
            $discountContext,
            [$discount1, $shippingDiscount2, $discount2, $shippingDiscount1]
        );

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

        $this->assertContains($shippingDiscount1, $appliedDiscounts);
        $this->assertNotContains($shippingDiscount2, $appliedDiscounts);
        $this->assertEquals(65, $processedContext->getShippingCost());
    }

    /**
     * @param float $amount
     * @return OrderDiscount
     */
    private function createOrderDiscount($amount): OrderDiscount
    {
        $discount = new OrderDiscount();
        $discount->configure([
            AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
            AbstractDiscount::DISCOUNT_VALUE => $amount,
            AbstractDiscount::DISCOUNT_CURRENCY => 'USD',
        ]);

        return $discount;
    }

    /**
     * @param float $amount
     * @return ShippingDiscount
     */
    private function createShippingDiscount($amount): ShippingDiscount
    {
        $discount = new ShippingDiscount();
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
