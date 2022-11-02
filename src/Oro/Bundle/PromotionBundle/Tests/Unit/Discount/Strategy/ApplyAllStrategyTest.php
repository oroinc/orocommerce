<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount\Strategy;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalAwareInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountInformation;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Discount\Strategy\ApplyAllStrategy;

class ApplyAllStrategyTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ApplyAllStrategy
     */
    private $strategy;

    protected function setUp(): void
    {
        $this->strategy = new ApplyAllStrategy();
    }

    public function testGetLabel(): void
    {
        $this->assertEquals('oro.promotion.discount.strategy.apply_all.label', $this->strategy->getLabel());
    }

    /**
     * @dataProvider processProvider
     * @param DiscountContext $discountContext
     * @param float $contextSubtotalAmount
     * @param float $shippingCost
     * @param array $discounts
     * @param float $expectedSubtotal
     * @param float $expectedShippingCost
     * @param array $expectedDiscountsInformation
     * @param array $expectedLineItemsSubtotalAfterDiscounts
     */
    public function testProcess(
        DiscountContext $discountContext,
        $contextSubtotalAmount,
        $shippingCost,
        array $discounts,
        $expectedSubtotal,
        $expectedShippingCost,
        array $expectedDiscountsInformation,
        array $expectedLineItemsSubtotalAfterDiscounts
    ): void {
        $discountContext->setShippingCost($shippingCost);
        $discountContext->setSubtotal($contextSubtotalAmount);

        $this->strategy->process($discountContext, $discounts);
        $this->assertEquals($expectedSubtotal, $discountContext->getSubtotal());
        $this->assertEquals($expectedShippingCost, $discountContext->getShippingCost());
        $this->assertEquals(
            $expectedDiscountsInformation['lineItems'],
            $this->getLineItemsDiscountsInformation($discountContext)
        );
        $this->assertEquals(
            $expectedDiscountsInformation['subtotal'],
            $discountContext->getSubtotalDiscountsInformation()
        );
        $this->assertEquals(
            $expectedDiscountsInformation['shipping'],
            $discountContext->getShippingDiscountsInformation()
        );

        $this->assertEquals(
            $expectedLineItemsSubtotalAfterDiscounts,
            $this->getLineItemsSubtotalsAfterDiscounts($discountContext)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function processProvider(): array
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
        $subtotalDiscountAmount1 = 100.00;
        $subtotalDiscountAmount2 = 50.00;
        $subtotalDiscount1 = $this->createDiscount($discountContext, $discountContext, $subtotalDiscountAmount1);
        $subtotalDiscount2 = $this->createDiscount($discountContext, $discountContext, $subtotalDiscountAmount2);
        $discountContext->addSubtotalDiscount($subtotalDiscount1);
        $discountContext->addSubtotalDiscount($subtotalDiscount2);

        // Add shipping discounts
        $discountContext->setShippingCost(30.00);
        $shippingDiscountAmount1 = 5.00;
        $shippingDiscountAmount2 = 3.00;
        $shippingDiscount1 = $this->createDiscount($discountContext, $discountContext, $shippingDiscountAmount1);
        $shippingDiscount2 = $this->createDiscount($discountContext, $discountContext, $shippingDiscountAmount2);
        $discountContext->addShippingDiscount($shippingDiscount1);
        $discountContext->addShippingDiscount($shippingDiscount2);

        // Create new context for case when discount amounts greater than subtotal and shipping cost
        $newDiscountContext = new DiscountContext();
        $newSubtotalDiscountAmount = 100.00;
        $newSubtotalDiscount = $this->createDiscount(
            $newDiscountContext,
            $newDiscountContext,
            $newSubtotalDiscountAmount
        );
        $newDiscountContext->addSubtotalDiscount($newSubtotalDiscount);
        $newShippingDiscountAmount = 50.00;
        $newShippingDiscount = $this->createDiscount(
            $newDiscountContext,
            $newDiscountContext,
            $newShippingDiscountAmount
        );
        $newDiscountContext->addShippingDiscount($newShippingDiscount);

        return [
            'when discount amounts greater than subtotal' => [
                'context' => $discountContext,
                'contextSubtotalAmount' => 1000.00,
                'shippingCost' => 30.00,
                'discounts' => [
                    $lineItemSubtotalDiscount1,
                    $lineItemSubtotalDiscount2,
                    $lineItemSubtotalDiscount22,
                    $subtotalDiscount1,
                    $subtotalDiscount2,
                    $shippingDiscount1,
                    $shippingDiscount2,
                ],
                'expectedSubtotal' => 850.00,
                'expectedShippingCost' => 22.00,
                'expectedDiscountsInformation' => [
                    'lineItems' => [
                        new DiscountInformation($lineItemSubtotalDiscount1, 0.00),
                        new DiscountInformation($lineItemSubtotalDiscount2, 0.00),
                        new DiscountInformation($lineItemSubtotalDiscount22, 0.00),
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
                'contextSubtotalAmount' => 80.00,
                'shippingCost' => 20.00,
                'discounts' => [
                    $newSubtotalDiscount,
                    $newShippingDiscount,
                ],
                'expectedSubtotal' => 0.00,
                'expectedShippingCost' => 0.00,
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

    /**
     * @param DiscountContext $discountContext
     * @param SubtotalAwareInterface $subtotalAware
     * @param float $discountAmount
     * @return DiscountInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createDiscount(
        DiscountContext $discountContext,
        SubtotalAwareInterface $subtotalAware,
        $discountAmount = 0.00
    ) {
        $discount = $this->createMock(DiscountInterface::class);
        $discount->expects($this->any())
            ->method('calculate')
            ->with($subtotalAware)
            ->willReturn($discountAmount);
        $discount->expects($this->any())
            ->method('apply')
            ->with($discountContext);

        return $discount;
    }

    /**
     * @param DiscountContext $discountContext
     * @return array|DiscountInformation[]
     */
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
