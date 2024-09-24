<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount\Strategy;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalAwareInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountInformation;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Discount\ShippingDiscount;
use Oro\Bundle\PromotionBundle\Discount\Strategy\ApplyAllStrategy;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\PromotionBundle\Model\MultiShippingPromotionData;

class ApplyAllStrategyTest extends \PHPUnit\Framework\TestCase
{
    private ApplyAllStrategy $strategy;

    #[\Override]
    protected function setUp(): void
    {
        $this->strategy = new ApplyAllStrategy();
    }

    public function testGetLabel(): void
    {
        self::assertEquals('oro.promotion.discount.strategy.apply_all.label', $this->strategy->getLabel());
    }

    /**
     * @dataProvider processDataProvider
     */
    public function testProcess(
        DiscountContext $discountContext,
        float $contextSubtotalAmount,
        float $shippingCost,
        array $discounts,
        float $expectedSubtotal,
        float $expectedShippingCost,
        array $expectedDiscountsInformation,
        array $expectedLineItemsSubtotalAfterDiscounts
    ): void {
        $discountContext->setShippingCost($shippingCost);
        $discountContext->setSubtotal($contextSubtotalAmount);

        $this->strategy->process($discountContext, $discounts);
        self::assertEquals($expectedSubtotal, $discountContext->getSubtotal());
        self::assertEquals($expectedShippingCost, $discountContext->getShippingCost());
        self::assertEquals(
            $expectedDiscountsInformation['lineItems'],
            $this->getLineItemsDiscountsInformation($discountContext)
        );
        self::assertEquals(
            $expectedDiscountsInformation['subtotal'],
            $discountContext->getSubtotalDiscountsInformation()
        );
        self::assertEquals(
            $expectedDiscountsInformation['shipping'],
            $discountContext->getShippingDiscountsInformation()
        );

        self::assertEquals(
            $expectedLineItemsSubtotalAfterDiscounts,
            $this->getLineItemsSubtotalsAfterDiscounts($discountContext)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function processDataProvider(): array
    {
        $discountContext = new DiscountContext();

        // Add line items with discounts
        $discountLineItem1 = new DiscountLineItem();
        $discountLineItem1->setSubtotal(800);
        $lineItemSubtotalDiscount1 = $this->createDiscount($discountContext, $discountLineItem1);
        $discountLineItem1->addDiscount($lineItemSubtotalDiscount1);
        $discountLineItem2 = new DiscountLineItem();
        $discountLineItem2->setSubtotal(200);
        $lineItemSubtotalDiscount2 = $this->createDiscount($discountContext, $discountLineItem2);
        $lineItemSubtotalDiscount22 = $this->createDiscount($discountContext, $discountLineItem2);
        $discountLineItem2
            ->addDiscount($lineItemSubtotalDiscount2)
            ->addDiscount($lineItemSubtotalDiscount22);
        $discountContext->setLineItems([$discountLineItem1, $discountLineItem2]);

        // Add subtotal discounts
        $subtotalDiscountAmount1 = 100.0;
        $subtotalDiscountAmount2 = 50.0;
        $subtotalDiscount1 = $this->createDiscount($discountContext, $discountContext, $subtotalDiscountAmount1);
        $subtotalDiscount2 = $this->createDiscount($discountContext, $discountContext, $subtotalDiscountAmount2);
        $discountContext->addSubtotalDiscount($subtotalDiscount1);
        $discountContext->addSubtotalDiscount($subtotalDiscount2);

        // Add shipping discounts
        $discountContext->setShippingCost(30.0);
        $shippingDiscountAmount1 = 5.0;
        $shippingDiscountAmount2 = 3.0;
        $shippingDiscount1 = $this->createShippingDiscount($shippingDiscountAmount1);
        $shippingDiscount2 = $this->createShippingDiscount($shippingDiscountAmount2);

        // Create new context for case when discount amounts greater than subtotal and shipping cost
        $newDiscountContext = new DiscountContext();
        $newSubtotalDiscountAmount = 100.0;
        $newSubtotalDiscount = $this->createDiscount(
            $newDiscountContext,
            $newDiscountContext,
            $newSubtotalDiscountAmount
        );
        $newDiscountContext->addSubtotalDiscount($newSubtotalDiscount);
        $newShippingDiscountAmount = 50.0;
        $newShippingDiscount = $this->createDiscount(
            $newDiscountContext,
            $newDiscountContext,
            $newShippingDiscountAmount
        );
        $newDiscountContext->addShippingDiscount($newShippingDiscount);

        return [
            'when discount amounts greater than subtotal' => [
                'context' => $discountContext,
                'contextSubtotalAmount' => 1000.0,
                'shippingCost' => 30.0,
                'discounts' => [
                    $lineItemSubtotalDiscount1,
                    $lineItemSubtotalDiscount2,
                    $lineItemSubtotalDiscount22,
                    $subtotalDiscount1,
                    $subtotalDiscount2,
                    $shippingDiscount1,
                    $shippingDiscount2,
                ],
                'expectedSubtotal' => 850.0,
                'expectedShippingCost' => 22.0,
                'expectedDiscountsInformation' => [
                    'lineItems' => [
                        new DiscountInformation($lineItemSubtotalDiscount1, 0.0),
                        new DiscountInformation($lineItemSubtotalDiscount2, 0.0),
                        new DiscountInformation($lineItemSubtotalDiscount22, 0.0),
                    ],
                    'subtotal' => [
                        new DiscountInformation($subtotalDiscount1, $subtotalDiscountAmount1),
                        new DiscountInformation($subtotalDiscount2, $subtotalDiscountAmount2),
                    ],
                    'shipping' => [
                        new DiscountInformation($shippingDiscount1, $shippingDiscountAmount1),
                        new DiscountInformation($shippingDiscount2, $shippingDiscountAmount2),
                    ],
                ],
                'expectedLineItemsSubtotalAfterDiscounts' => [680, 170]
            ],
            'when discount amounts less than subtotal' => [
                'context' => $newDiscountContext,
                'contextSubtotalAmount' => 80.0,
                'shippingCost' => 20.0,
                'discounts' => [
                    $newSubtotalDiscount,
                    $newShippingDiscount,
                ],
                'expectedSubtotal' => 0.0,
                'expectedShippingCost' => 0.0,
                'expectedDiscountsInformation' => [
                    'lineItems' => [],
                    'subtotal' => [
                        new DiscountInformation($newSubtotalDiscount, $newSubtotalDiscountAmount),
                    ],
                    'shipping' => [
                        new DiscountInformation($newShippingDiscount, $newShippingDiscountAmount),
                    ],
                ],
                'expectedLineItemsSubtotalAfterDiscounts' => []
            ],
        ];
    }

    public function testProcessMultiShippingDiscounts(): void
    {
        $discountContext = new DiscountContext();
        $discountContext->setShippingCost(30.0);
        $discountContext->setSubtotal(1000.0);

        $shippingDiscountAmount1 = 10.0;
        $multiShippingDiscountAmount2 = 3.0;
        $multiShippingDiscountAmount3 = 1.0;
        $shippingDiscountPromotion1 = new Promotion();
        $shippingDiscountPromotion2 = $this->createMock(MultiShippingPromotionData::class);
        $shippingDiscountPromotion2->expects(self::once())
            ->method('getShippingCost')
            ->willReturn(Price::create($multiShippingDiscountAmount2, 'USD'));
        $shippingDiscountPromotion3 = $this->createMock(MultiShippingPromotionData::class);
        $shippingDiscountPromotion3->expects(self::once())
            ->method('getShippingCost')
            ->willReturn(Price::create($multiShippingDiscountAmount3, 'USD'));
        $shippingDiscount1 = $this->createShippingDiscount($shippingDiscountAmount1, $shippingDiscountPromotion1);
        $shippingDiscount2 = $this->createShippingDiscount(5.0, $shippingDiscountPromotion2);
        $shippingDiscount3 = $this->createShippingDiscount(4.0, $shippingDiscountPromotion3);

        $this->strategy->process($discountContext, [$shippingDiscount1, $shippingDiscount2, $shippingDiscount3]);
        self::assertEquals(1000.0, $discountContext->getSubtotal());
        self::assertEquals(16.0, $discountContext->getShippingCost());
        self::assertEquals(
            [
                new DiscountInformation($shippingDiscount1, $shippingDiscountAmount1),
                new DiscountInformation($shippingDiscount2, $multiShippingDiscountAmount2),
                new DiscountInformation($shippingDiscount3, $multiShippingDiscountAmount3)
            ],
            $discountContext->getShippingDiscountsInformation()
        );
    }

    private function createDiscount(
        DiscountContext $discountContext,
        SubtotalAwareInterface $subtotalAware,
        float $discountAmount = 0.0
    ): DiscountInterface {
        $discount = $this->createMock(DiscountInterface::class);
        $discount->expects(self::any())
            ->method('calculate')
            ->with($subtotalAware)
            ->willReturn($discountAmount);
        $discount->expects(self::any())
            ->method('apply')
            ->with($discountContext);

        return $discount;
    }
    private function createShippingDiscount(float $amount, ?PromotionDataInterface $promotion = null): ShippingDiscount
    {
        $discount = new ShippingDiscount();
        $discount->configure([
            ShippingDiscount::DISCOUNT_TYPE => ShippingDiscount::TYPE_AMOUNT,
            ShippingDiscount::DISCOUNT_VALUE => $amount,
            ShippingDiscount::DISCOUNT_CURRENCY => 'USD',
        ]);
        if (null !== $promotion) {
            $discount->setPromotion($promotion);
        }

        return $discount;
    }

    private function getLineItemsDiscountsInformation(DiscountContext $discountContext): array
    {
        $discountsInformation = [];
        foreach ($discountContext->getLineItems() as $lineItem) {
            foreach ($lineItem->getDiscountsInformation() as $discountInformation) {
                $discountsInformation[] = $discountInformation;
            }
        }

        return $discountsInformation;
    }

    private function getLineItemsSubtotalsAfterDiscounts(DiscountContext $discountContext): array
    {
        $subtotals = [];
        foreach ($discountContext->getLineItems() as $lineItem) {
            $subtotals[] = $lineItem->getSubtotalAfterDiscounts();
        }

        return $subtotals;
    }
}
