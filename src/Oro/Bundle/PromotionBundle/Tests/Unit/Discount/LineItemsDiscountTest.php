<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\PromotionBundle\Discount\AbstractDiscount;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Discount\DiscountProductUnitCodeAwareInterface;
use Oro\Bundle\PromotionBundle\Discount\LineItemsDiscount;
use Oro\Component\Testing\Unit\EntityTrait;

class LineItemsDiscountTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private LineItemsDiscount $discount;

    protected function setUp(): void
    {
        $this->discount = new LineItemsDiscount();
    }

    public function testApply()
    {
        $product1 = $this->getEntity(Product::class, ['id' => 1, 'sku' => 'PROD_1']);
        $product2 = $this->getEntity(Product::class, ['id' => 2, 'sku' => 'PROD_2']);
        $product3 = $this->getEntity(Product::class, ['id' => 3, 'sku' => 'PROD_3']);

        $lineItem1 = new DiscountLineItem();
        $lineItem1->setProduct($product1);
        $lineItem1->setProductUnit((new ProductUnit())->setCode('item'));

        $lineItem2 = new DiscountLineItem();
        $lineItem2->setProduct($product2);
        $lineItem2->setProductUnit((new ProductUnit())->setCode('set'));

        $lineItem3 = new DiscountLineItem();
        $lineItem3->setProduct($product3);
        $lineItem3->setProductUnit((new ProductUnit())->setCode('item'));

        $discountContext = new DiscountContext();
        $discountContext->setLineItems([$lineItem1, $lineItem2, $lineItem3]);

        $this->discount->setMatchingProducts([$product1, $product2]);

        $options = [
            DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'item',
            AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_PERCENT,
            AbstractDiscount::DISCOUNT_VALUE => 0.15
        ];

        $this->discount->configure($options);
        $this->discount->apply($discountContext);

        $expectedLineItems = [
            $lineItem1->addDiscount($this->discount),
            $lineItem2,
            $lineItem3
        ];

        $this->assertSame($expectedLineItems, $discountContext->getLineItems());
    }

    /**
     * @dataProvider calculateDataProvider
     */
    public function testCalculate(array $options, float $subtotal, float $lineItemQuantity, float $expectedDiscount)
    {
        $discountLineItem = new DiscountLineItem();
        $discountLineItem->setSubtotal($subtotal);
        $discountLineItem->setQuantity($lineItemQuantity);

        $this->discount->configure($options);

        $actual = $this->discount->calculate($discountLineItem);
        $this->assertSame($expectedDiscount, $actual);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function calculateDataProvider(): array
    {
        return [
            'fixed amount discount each item without maximum qty' => [
                'options' => [
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'item',
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    AbstractDiscount::DISCOUNT_VALUE => 2,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'USD',
                    LineItemsDiscount::APPLY_TO => LineItemsDiscount::EACH_ITEM,
                ],
                'subtotal' => 100.0,
                'lineItemQuantity' => 10,
                'expectedDiscount' => 20.0
            ],
            'fixed amount discount greater than amount' => [
                'options' => [
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'item',
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    AbstractDiscount::DISCOUNT_VALUE => 200,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'USD',
                    LineItemsDiscount::APPLY_TO => LineItemsDiscount::LINE_ITEMS_TOTAL,
                ],
                'subtotal' => 100.0,
                'lineItemQuantity' => 10,
                'expectedDiscount' => 100.0
            ],
            'fixed amount discount each item with maximum qty' => [
                'options' => [
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'item',
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    AbstractDiscount::DISCOUNT_VALUE => 2,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'USD',
                    LineItemsDiscount::APPLY_TO => LineItemsDiscount::EACH_ITEM,
                    LineItemsDiscount::MAXIMUM_QTY => 10
                ],
                'subtotal' => 100.0,
                'lineItemQuantity' => 10,
                'expectedDiscount' => 20.0
            ],
            'fixed amount discount each item with maximum qty overprice discount' => [
                'options' => [
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'item',
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    AbstractDiscount::DISCOUNT_VALUE => 200,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'USD',
                    LineItemsDiscount::APPLY_TO => LineItemsDiscount::EACH_ITEM,
                    LineItemsDiscount::MAXIMUM_QTY => 5
                ],
                'subtotal' => 1000.0,
                'lineItemQuantity' => 10,
                'expectedDiscount' => 500.0
            ],
            'fixed amount discount each item with maximum qty over limit' => [
                'options' => [
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'item',
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    AbstractDiscount::DISCOUNT_VALUE => 2,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'USD',
                    LineItemsDiscount::APPLY_TO => LineItemsDiscount::EACH_ITEM,
                    LineItemsDiscount::MAXIMUM_QTY => 5
                ],
                'subtotal' => 100.0,
                'lineItemQuantity' => 10,
                'expectedDiscount' => 10.0
            ],
            'fixed amount discount line items total without maximum qty' => [
                'options' => [
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'item',
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    AbstractDiscount::DISCOUNT_VALUE => 10,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'USD',
                    LineItemsDiscount::APPLY_TO => LineItemsDiscount::LINE_ITEMS_TOTAL,
                    LineItemsDiscount::MAXIMUM_QTY => 0
                ],
                'subtotal' => 100.0,
                'lineItemQuantity' => 10,
                'expectedDiscount' => 10.0
            ],
            'fixed amount discount line items total with maximum qty' => [
                'options' => [
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'item',
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    AbstractDiscount::DISCOUNT_VALUE => 10,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'USD',
                    LineItemsDiscount::APPLY_TO => LineItemsDiscount::LINE_ITEMS_TOTAL,
                    LineItemsDiscount::MAXIMUM_QTY => 10
                ],
                'subtotal' => 100.0,
                'lineItemQuantity' => 10,
                'expectedDiscount' => 10.0
            ],
            'fixed amount discount line items total with maximum qty over limit' => [
                'options' => [
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'item',
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    AbstractDiscount::DISCOUNT_VALUE => 10,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'USD',
                    LineItemsDiscount::APPLY_TO => LineItemsDiscount::LINE_ITEMS_TOTAL,
                    LineItemsDiscount::MAXIMUM_QTY => 1
                ],
                'subtotal' => 100.0,
                'lineItemQuantity' => 10,
                'expectedDiscount' => 10.0
            ],
            'percent amount discount each item without maximum qty' => [
                'options' => [
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'item',
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_PERCENT,
                    AbstractDiscount::DISCOUNT_VALUE => 0.15,
                    LineItemsDiscount::APPLY_TO => LineItemsDiscount::EACH_ITEM,
                    LineItemsDiscount::MAXIMUM_QTY => 0
                ],
                'subtotal' => 200.0,
                'lineItemQuantity' => 2,
                'expectedDiscount' => 30.0
            ],
            'percent amount discount each item with maximum qty' => [
                'options' => [
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'item',
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_PERCENT,
                    AbstractDiscount::DISCOUNT_VALUE => 0.5,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'USD',
                    LineItemsDiscount::APPLY_TO => LineItemsDiscount::EACH_ITEM,
                    LineItemsDiscount::MAXIMUM_QTY => 5
                ],
                'subtotal' => 300.0,
                'lineItemQuantity' => 3,
                'expectedDiscount' => 150.0
            ],
            'percent amount discount each item with maximum qty over limit' => [
                'options' => [
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'item',
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_PERCENT,
                    AbstractDiscount::DISCOUNT_VALUE => 0.5,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'USD',
                    LineItemsDiscount::APPLY_TO => LineItemsDiscount::EACH_ITEM,
                    LineItemsDiscount::MAXIMUM_QTY => 1
                ],
                'subtotal' => 300.0,
                'lineItemQuantity' => 3,
                'expectedDiscount' => 50.0
            ],
            'percent amount discount line items total without maximum qty' => [
                'options' => [
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'item',
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_PERCENT,
                    AbstractDiscount::DISCOUNT_VALUE => 0.10,
                    LineItemsDiscount::APPLY_TO => LineItemsDiscount::LINE_ITEMS_TOTAL,
                    LineItemsDiscount::MAXIMUM_QTY => null
                ],
                'subtotal' => 1000.0,
                'lineItemQuantity' => 15,
                'expectedDiscount' => 100.0
            ],
            'percent amount discount line items total with maximum qty' => [
                'options' => [
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'item',
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_PERCENT,
                    AbstractDiscount::DISCOUNT_VALUE => 0.10,
                    LineItemsDiscount::APPLY_TO => LineItemsDiscount::LINE_ITEMS_TOTAL,
                    LineItemsDiscount::MAXIMUM_QTY => 100
                ],
                'subtotal' => 1000.0,
                'lineItemQuantity' => 15,
                'expectedDiscount' => 100.0
            ],
            'percent amount discount line items total with maximum qty over limit' => [
                'options' => [
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'item',
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_PERCENT,
                    AbstractDiscount::DISCOUNT_VALUE => 0.10,
                    LineItemsDiscount::APPLY_TO => LineItemsDiscount::LINE_ITEMS_TOTAL,
                    LineItemsDiscount::MAXIMUM_QTY => 5
                ],
                'subtotal' => 1000.0,
                'lineItemQuantity' => 10,
                'expectedDiscount' => 50.0
            ]
        ];
    }

    public function testCalculateNotDiscountLineItem()
    {
        $this->assertSame(0.0, $this->discount->calculate(new \stdClass()));
    }

    /**
     * @dataProvider calculateZeroQtyDataProvider
     */
    public function testCalculateNotPositiveQty(float $qty)
    {
        $discountLineItem = new DiscountLineItem();
        $discountLineItem->setSubtotal(100);
        $discountLineItem->setQuantity($qty);

        $this->assertSame(0.0, $this->discount->calculate($discountLineItem));
    }

    public function calculateZeroQtyDataProvider(): array
    {
        return [
            'zero qty' => [
                'qty' => 0
            ],
            'sub zero qty' => [
                'qty' => -5
            ]
        ];
    }
}
