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
    private ProfitableStrategy $strategy;

    #[\Override]
    protected function setUp(): void
    {
        $this->strategy = new ProfitableStrategy();
    }

    public function testGetLabel(): void
    {
        self::assertEquals('oro.promotion.discount.strategy.profitable.label', $this->strategy->getLabel());
    }

    public function testProcess(): void
    {
        $discount1 = $this->createOrderDiscount(10);
        $discount2 = $this->createOrderDiscount(20);

        $shippingDiscount1 = $this->createShippingDiscount(15);
        $shippingDiscount2 = $this->createShippingDiscount(5);

        $discountContext = new DiscountContext();
        $discountContext->setLineItems([
            (new DiscountLineItem())->setSubtotal(100),
            (new DiscountLineItem())->setSubtotal(200)
        ]);
        $discountContext->setSubtotal(300);
        $discountContext->setShippingCost(80);

        $processedContext = $this->strategy->process(
            $discountContext,
            [$discount1, $shippingDiscount2, $discount2, $shippingDiscount1]
        );

        self::assertInstanceOf(DiscountContext::class, $processedContext);
        $appliedDiscounts = [];
        foreach ($processedContext->getLineItems() as $lineItem) {
            $appliedDiscounts = array_merge($appliedDiscounts, $lineItem->getDiscounts());
        }
        $appliedDiscounts = array_merge($appliedDiscounts, $processedContext->getSubtotalDiscounts());
        $appliedDiscounts = array_merge($appliedDiscounts, $processedContext->getShippingDiscounts());

        self::assertNotEmpty($appliedDiscounts);

        self::assertContains($discount2, $appliedDiscounts);
        self::assertNotContains($discount1, $appliedDiscounts);
        self::assertEquals(280, $processedContext->getSubtotal());

        self::assertContains($shippingDiscount1, $appliedDiscounts);
        self::assertNotContains($shippingDiscount2, $appliedDiscounts);
        self::assertEquals(65, $processedContext->getShippingCost());
    }

    private function createOrderDiscount(float $amount): OrderDiscount
    {
        $discount = new OrderDiscount();
        $discount->configure([
            AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
            AbstractDiscount::DISCOUNT_VALUE => $amount,
            AbstractDiscount::DISCOUNT_CURRENCY => 'USD',
        ]);

        return $discount;
    }

    private function createShippingDiscount(float $amount): ShippingDiscount
    {
        $discount = new ShippingDiscount();
        $discount->configure([
            AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
            AbstractDiscount::DISCOUNT_VALUE => $amount,
            AbstractDiscount::DISCOUNT_CURRENCY => 'USD',
        ]);

        return $discount;
    }
}
