<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalAwareInterface;
use Oro\Bundle\PromotionBundle\Discount\AbstractDiscount;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Discount\OrderDiscount;
use Oro\Bundle\PromotionBundle\Discount\ShippingAwareDiscount;
use Oro\Bundle\PromotionBundle\Discount\ShippingDiscount;

class OrderDiscountTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DiscountInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingDiscount;

    /**
     * @var OrderDiscount
     */
    private $discount;

    protected function setUp()
    {
        $this->shippingDiscount = $this->createMock(DiscountInterface::class);
        $this->discount = new OrderDiscount($this->shippingDiscount);
    }

    public function testApplyWithoutShippingDiscount()
    {
        /** @var DiscountContext|\PHPUnit_Framework_MockObject_MockObject $discountContext */
        $discountContext = $this->createMock(DiscountContext::class);
        $discountContext->expects($this->once())
            ->method('addSubtotalDiscount')
            ->with($this->discount);

        $options = [
            AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_PERCENT,
            AbstractDiscount::DISCOUNT_VALUE => 0.2
        ];
        $this->discount->configure($options);

        $this->discount->apply($discountContext);
    }

    public function testApplyWithShippingDiscount()
    {
        /** @var DiscountContext|\PHPUnit_Framework_MockObject_MockObject $discountContext */
        $discountContext = $this->createMock(DiscountContext::class);
        $discountContext->expects($this->once())
            ->method('addSubtotalDiscount')
            ->with($this->discount);
        $discountContext->expects($this->once())
            ->method('addShippingDiscount')
            ->with($this->shippingDiscount);

        $options = [
            AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_PERCENT,
            AbstractDiscount::DISCOUNT_VALUE => 0.2,
            ShippingAwareDiscount::SHIPPING_DISCOUNT => ShippingDiscount::APPLY_TO_ITEMS
        ];
        $this->discount->configure($options);

        $this->discount->apply($discountContext);
    }

    public function testCalculateNonSupportedEntity()
    {
        $entity = new \stdClass();
        $options = [
            AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_PERCENT,
            AbstractDiscount::DISCOUNT_VALUE => 0.2
        ];

        $this->discount->configure($options);

        $this->assertSame(0.0, $this->discount->calculate($entity));
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
        $entity->expects($this->any())
            ->method('getSubtotal')
            ->willReturn($subtotal);

        $this->discount->configure($options);

        $this->assertSame($expected, $this->discount->calculate($entity));
    }

    /**
     * @return array
     */
    public function calculateDataProvider(): array
    {
        return [
            'fixed amount > subtotal' => [
                [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    AbstractDiscount::DISCOUNT_VALUE => 100.2,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'EUR'
                ],
                100.0,
                100.0
            ],
            'fixed amount < subtotal' => [
                [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    AbstractDiscount::DISCOUNT_VALUE => 100.2,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'EUR'
                ],
                200.0,
                100.2
            ],
            'percent' => [
                [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_PERCENT,
                    AbstractDiscount::DISCOUNT_VALUE => 0.2
                ],
                100.0,
                20.0
            ]
        ];
    }
}
