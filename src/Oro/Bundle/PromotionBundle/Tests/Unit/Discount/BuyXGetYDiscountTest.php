<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\PromotionBundle\Discount\AbstractDiscount;
use Oro\Bundle\PromotionBundle\Discount\BuyXGetYDiscount;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Discount\DiscountProductUnitCodeAwareInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class BuyXGetYDiscountTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var BuyXGetYDiscount
     */
    private $discount;

    protected function setUp(): void
    {
        $this->discount = new BuyXGetYDiscount();
    }

    public function testApply()
    {
        $matchingProduct = $this->getEntity(Product::class, ['id' => 42]);
        $notMatchingProduct = $this->getEntity(Product::class, ['id' => 123]);
        $lineItemWithDiscount = (new DiscountLineItem())
            ->setProduct($matchingProduct)
            ->setQuantity(10.0)
            ->setProductUnitCode('unit');
        $lineItemWithFalseUnitCode = (clone $lineItemWithDiscount)
            ->setProductUnitCode('some another unit');
        $lineItemWithFalseQuantity = (clone $lineItemWithDiscount)
            ->setQuantity(2.0);
        $lineItemWithFalseProduct = (clone $lineItemWithDiscount)
            ->setProduct($notMatchingProduct);

        $lineItems = [
            $lineItemWithDiscount,
            $lineItemWithFalseUnitCode,
            $lineItemWithFalseQuantity,
            $lineItemWithFalseProduct,
        ];
        $discountContext = (new DiscountContext())
            ->setLineItems($lineItems);

        $options = [
            AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_PERCENT,
            AbstractDiscount::DISCOUNT_VALUE => 0.2,
            DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'unit',
            BuyXGetYDiscount::BUY_X => 2,
            BuyXGetYDiscount::GET_Y => 3,
        ];
        $this->discount->configure($options);
        $this->discount->setMatchingProducts([$matchingProduct]);

        $this->discount->apply($discountContext);
        $this->assertEmpty($lineItemWithFalseUnitCode->getDiscounts());
        $this->assertEmpty($lineItemWithFalseQuantity->getDiscounts());
        $this->assertEmpty($lineItemWithFalseProduct->getDiscounts());
        $this->assertCount(1, $lineItemWithDiscount->getDiscounts());
        $this->assertEquals($this->discount, $lineItemWithDiscount->getDiscounts()[0]);
    }

    /**
     * @dataProvider invalidOptionsDataProvider
     */
    public function testInvalidOptions(array $options)
    {
        $this->expectException(InvalidOptionsException::class);
        $this->discount->configure($options);
    }

    public function invalidOptionsDataProvider(): array
    {
        return [
            'invalid BUY_X type' => [
                [
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'unit',
                    BuyXGetYDiscount::BUY_X => 1.0,
                ],
            ],
            'invalid GET_Y type' => [
                [
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'unit',
                    BuyXGetYDiscount::GET_Y => 'abc',
                ],
            ],
            'invalid DISCOUNT_LIMIT type' => [
                [
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'unit',
                    BuyXGetYDiscount::DISCOUNT_LIMIT => 'abc',
                ],
            ],
            'invalid DISCOUNT_APPLY_TO type' => [
                [
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'unit',
                    BuyXGetYDiscount::DISCOUNT_APPLY_TO => 100,
                ],
            ],
            'invalid DISCOUNT_APPLY_TO values' => [
                [
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'unit',
                    BuyXGetYDiscount::DISCOUNT_APPLY_TO => 'abc',
                ],
            ],
            'invalid DISCOUNT_PRODUCT_UNIT_CODE type' => [
                [
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 100,
                ],
            ],
        ];
    }

    public function testCalculateNonSupportedEntity()
    {
        $entity = new \stdClass();
        $this->assertSame(0.0, $this->discount->calculate($entity));
    }

    public function testCalculateIfQuantityEqualsZero()
    {
        $entity = new DiscountLineItem();
        $entity->setQuantity(0);
        $this->assertSame(0.0, $this->discount->calculate($entity));
    }

    /**
     * @dataProvider calculateDataProvider
     *
     * @param array $options
     * @param DiscountLineItem $entity
     * @param float $expected
     */
    public function testCalculate(array $options, DiscountLineItem $entity, $expected)
    {
        $this->discount->configure($options);

        $this->assertSame($expected, $this->discount->calculate($entity));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function calculateDataProvider(): array
    {
        return [
            'without limit, type amount, apply to each y' => [
                'options' => [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    AbstractDiscount::DISCOUNT_VALUE => 5,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'EUR',
                    BuyXGetYDiscount::BUY_X => 3,
                    BuyXGetYDiscount::GET_Y => 2,
                    BuyXGetYDiscount::DISCOUNT_APPLY_TO => BuyXGetYDiscount::APPLY_TO_EACH_Y,
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'unit',
                ],
                'entity' => (new DiscountLineItem())
                    ->setQuantity(21)
                    ->setSubtotal(1000),
                40.0
            ],
            'without limit, type amount, apply to xy total' => [
                'options' => [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    AbstractDiscount::DISCOUNT_VALUE => 5,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'EUR',
                    BuyXGetYDiscount::BUY_X => 3,
                    BuyXGetYDiscount::GET_Y => 2,
                    BuyXGetYDiscount::DISCOUNT_APPLY_TO => BuyXGetYDiscount::APPLY_TO_XY_TOTAL,
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'unit',
                ],
                'entity' => (new DiscountLineItem())
                    ->setQuantity(21)
                    ->setSubtotal(1000),
                20.0
            ],
            'with limit, type amount, apply to xy total' => [
                'options' => [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    AbstractDiscount::DISCOUNT_VALUE => 5,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'EUR',
                    BuyXGetYDiscount::BUY_X => 3,
                    BuyXGetYDiscount::GET_Y => 2,
                    BuyXGetYDiscount::DISCOUNT_APPLY_TO => BuyXGetYDiscount::APPLY_TO_XY_TOTAL,
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'unit',
                    BuyXGetYDiscount::DISCOUNT_LIMIT => 2,
                ],
                'entity' => (new DiscountLineItem())
                    ->setQuantity(21)
                    ->setSubtotal(1000),
                10.0
            ],
            'with limit, type amount, apply to each y, when discount > amount per item' => [
                'options' => [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    AbstractDiscount::DISCOUNT_VALUE => 500,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'EUR',
                    BuyXGetYDiscount::BUY_X => 3,
                    BuyXGetYDiscount::GET_Y => 2,
                    BuyXGetYDiscount::DISCOUNT_APPLY_TO => BuyXGetYDiscount::APPLY_TO_EACH_Y,
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'unit',
                    BuyXGetYDiscount::DISCOUNT_LIMIT => 2,
                ],
                'entity' => (new DiscountLineItem())
                    ->setQuantity(20)
                    ->setSubtotal(1000),
                200.0
            ],
            'without limit, type amount, apply to xy total, when discount > subtotal' => [
                'options' => [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    AbstractDiscount::DISCOUNT_VALUE => 500,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'EUR',
                    BuyXGetYDiscount::BUY_X => 3,
                    BuyXGetYDiscount::GET_Y => 2,
                    BuyXGetYDiscount::DISCOUNT_APPLY_TO => BuyXGetYDiscount::APPLY_TO_XY_TOTAL,
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'unit',
                ],
                'entity' => (new DiscountLineItem())
                    ->setQuantity(21)
                    ->setSubtotal(1000),
                1000.0
            ],
            'without limit, type percent, apply to each y' => [
                'options' => [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_PERCENT,
                    AbstractDiscount::DISCOUNT_VALUE => 0.1,
                    BuyXGetYDiscount::BUY_X => 3,
                    BuyXGetYDiscount::GET_Y => 2,
                    BuyXGetYDiscount::DISCOUNT_APPLY_TO => BuyXGetYDiscount::APPLY_TO_EACH_Y,
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'unit',
                ],
                'entity' => (new DiscountLineItem())
                    ->setQuantity(20)
                    ->setSubtotal(1000),
                40.0
            ],
            'without limit, type percent, apply to xy total' => [
                'options' => [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_PERCENT,
                    AbstractDiscount::DISCOUNT_VALUE => 0.1,
                    BuyXGetYDiscount::BUY_X => 3,
                    BuyXGetYDiscount::GET_Y => 2,
                    BuyXGetYDiscount::DISCOUNT_APPLY_TO => BuyXGetYDiscount::APPLY_TO_XY_TOTAL,
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'unit',
                ],
                'entity' => (new DiscountLineItem())
                    ->setQuantity(20)
                    ->setSubtotal(1000),
                100.0
            ],
        ];
    }
}
