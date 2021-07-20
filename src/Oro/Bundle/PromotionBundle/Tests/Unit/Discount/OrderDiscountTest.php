<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalAwareInterface;
use Oro\Bundle\PromotionBundle\Discount\AbstractDiscount;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\OrderDiscount;

class OrderDiscountTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var OrderDiscount
     */
    private $discount;

    protected function setUp(): void
    {
        $this->discount = new OrderDiscount();
    }

    public function testApply()
    {
        $discountContext = new DiscountContext();

        $options = [
            AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_PERCENT,
            AbstractDiscount::DISCOUNT_VALUE => 0.2
        ];
        $this->discount->configure($options);

        $this->discount->apply($discountContext);
        $this->assertCount(1, $discountContext->getSubtotalDiscounts());
        $this->assertEquals($this->discount, $discountContext->getSubtotalDiscounts()[0]);
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
