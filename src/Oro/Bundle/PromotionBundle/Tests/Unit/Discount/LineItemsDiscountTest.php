<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalAwareInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\PromotionBundle\Discount\AbstractDiscount;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Discount\LineItemsDiscount;
use Oro\Component\Testing\Unit\EntityTrait;

class LineItemsDiscountTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var DiscountInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingDiscount;

    /**
     * @var LineItemsDiscount
     */
    protected $discount;

    protected function setUp()
    {
        $this->shippingDiscount = $this->createMock(DiscountInterface::class);
        $this->discount = new LineItemsDiscount($this->shippingDiscount);
    }

    public function testToStringFixedAmount()
    {
        $options = [
            AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
            AbstractDiscount::DISCOUNT_VALUE => 9.99,
            AbstractDiscount::DISCOUNT_CURRENCY => 'USD'
        ];

        $this->discount->configure($options);
        $this->assertEquals('Line Items Discount 9.99 USD', $this->discount->__toString());
    }

    public function testToStringPercent()
    {
        $options = [
            AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_PERCENT,
            AbstractDiscount::DISCOUNT_VALUE => 0.5,
        ];

        $this->discount->configure($options);
        $this->assertEquals('Line Items Discount 50%', $this->discount->__toString());
    }

    public function testApplyWithoutShippingDiscount()
    {
        $product1 = $this->getEntity(Product::class, ['id' => 1, 'sku' => 'PROD_1']);
        $product2 = $this->getEntity(Product::class, ['id' => 2, 'sku' => 'PROD_2']);
        $product3 = $this->getEntity(Product::class, ['id' => 3, 'sku' => 'PROD_3']);

        $lineItem1 = new DiscountLineItem();
        $lineItem1->setProduct($product1);

        $lineItem2 = new DiscountLineItem();
        $lineItem2->setProduct($product2);

        $lineItem3 = new DiscountLineItem();
        $lineItem3->setProduct($product3);

        $discountContext = new DiscountContext();
        $discountContext->setLineItems([$lineItem1, $lineItem2, $lineItem3]);

        $this->discount->setMatchingProducts(new ArrayCollection([$product1, $product2]));

        $options = [
            AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_PERCENT,
            AbstractDiscount::DISCOUNT_VALUE => 0.15
        ];

        $this->discount->configure($options);
        $this->discount->apply($discountContext);

        $expectedLineItems = [
            $lineItem1->addDiscount($this->discount),
            $lineItem2->addDiscount($this->discount),
            $lineItem3
        ];

        $this->assertSame($expectedLineItems, $discountContext->getLineItems());
    }

    /**
     * @dataProvider calculateDataProvider
     *
     * @param array $options
     * @param float $subtotal
     * @param float $expected
     */
    public function testCalculate(array $options, $subtotal, $expected)
    {
        $entity = $this->createMock(SubtotalAwareInterface::class);
        $entity->expects($this->any())->method('getSubtotal')->willReturn($subtotal);

        $this->discount->configure($options);

        $actual = $this->discount->calculate($entity);
        $this->assertSame($expected, $actual);
    }

    /**
     * @return array
     */
    public function calculateDataProvider(): array
    {
        return [
            'fixed amount discount > subtotal' => [
                [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    AbstractDiscount::DISCOUNT_VALUE => 100.9,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'EUR'
                ],
                100.0,
                100.0
            ],
            'fixed amount discount < subtotal' => [
                [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    AbstractDiscount::DISCOUNT_VALUE => 10.5,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'EUR'
                ],
                200.0,
                10.5
            ],
            'percent discount' => [
                [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_PERCENT,
                    AbstractDiscount::DISCOUNT_VALUE => 0.5
                ],
                100.0,
                50.0
            ]
        ];
    }
}
