<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Model\ShippingAwareInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\ShippingDiscount;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class ShippingDiscountTest extends \PHPUnit\Framework\TestCase
{
    private ShippingDiscount $discount;

    protected function setUp(): void
    {
        $this->discount = new ShippingDiscount();
    }

    /**
     * @dataProvider invalidOptionsDataProvider
     */
    public function testInvalidOptions(array $options): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->discount->configure($options);
    }

    public function invalidOptionsDataProvider(): array
    {
        return [
            'invalid DISCOUNT_TYPE type' => [
                [
                    ShippingDiscount::DISCOUNT_TYPE => []
                ]
            ],
            'invalid DISCOUNT_VALUE type' => [
                [
                    ShippingDiscount::DISCOUNT_VALUE => 'abc'
                ]
            ],
            'invalid DISCOUNT_CURRENCY type' => [
                [
                    ShippingDiscount::DISCOUNT_TYPE => ShippingDiscount::TYPE_AMOUNT,
                    ShippingDiscount::DISCOUNT_CURRENCY => 100
                ]
            ],
            'invalid DISCOUNT_CURRENCY code length' => [
                [
                    ShippingDiscount::DISCOUNT_TYPE => ShippingDiscount::TYPE_AMOUNT,
                    ShippingDiscount::DISCOUNT_CURRENCY => 'ABCD'
                ]
            ],
            'invalid SHIPPING_OPTIONS type' => [
                [
                    ShippingDiscount::SHIPPING_OPTIONS => 1.0,
                ]
            ]
        ];
    }

    public function testApply(): void
    {
        $discountContext = new DiscountContext();

        $options = [
            ShippingDiscount::DISCOUNT_TYPE => ShippingDiscount::TYPE_PERCENT,
            ShippingDiscount::DISCOUNT_VALUE => 0.2,
        ];
        $this->discount->configure($options);

        $this->discount->apply($discountContext);
        self::assertCount(1, $discountContext->getShippingDiscounts());
        self::assertEquals($this->discount, $discountContext->getShippingDiscounts()[0]);
    }

    public function testCalculateNonSupportedEntity(): void
    {
        $entity = new \stdClass();
        $options = [
            ShippingDiscount::DISCOUNT_TYPE => ShippingDiscount::TYPE_PERCENT,
            ShippingDiscount::DISCOUNT_VALUE => 0.2
        ];

        $this->discount->configure($options);

        self::assertSame(0.0, $this->discount->calculate($entity));
    }

    /**
     * @dataProvider calculateDataProvider
     */
    public function testCalculate(array $options, mixed $shippingCost, float $expectedDiscount): void
    {
        $entity = $this->createMock(ShippingAwareInterface::class);
        $entity->expects(self::once())
            ->method('getShippingCost')
            ->willReturn($shippingCost);

        $this->discount->configure($options);

        self::assertSame($expectedDiscount, $this->discount->calculate($entity));
    }

    public function calculateDataProvider(): array
    {
        return [
            'null shipping cost' => [
                'options' => [
                    ShippingDiscount::DISCOUNT_TYPE => ShippingDiscount::TYPE_AMOUNT,
                    ShippingDiscount::DISCOUNT_VALUE => 100.2,
                    ShippingDiscount::DISCOUNT_CURRENCY => 'EUR'
                ],
                'shippingCost' => null,
                'expectedDiscount' => 0.0
            ],
            'fixed amount > shipping cost' => [
                'options' => [
                    ShippingDiscount::DISCOUNT_TYPE => ShippingDiscount::TYPE_AMOUNT,
                    ShippingDiscount::DISCOUNT_VALUE => 100.2,
                    ShippingDiscount::DISCOUNT_CURRENCY => 'EUR'
                ],
                'shippingCost' => 100.0,
                'expectedDiscount' => 100.0
            ],
            'fixed amount < shipping cost' => [
                'options' => [
                    ShippingDiscount::DISCOUNT_TYPE => ShippingDiscount::TYPE_AMOUNT,
                    ShippingDiscount::DISCOUNT_VALUE => 100.2,
                    ShippingDiscount::DISCOUNT_CURRENCY => 'EUR'
                ],
                'shippingCost' => 200.0,
                'expectedDiscount' => 100.2
            ],
            'percent' => [
                'options' => [
                    ShippingDiscount::DISCOUNT_TYPE => ShippingDiscount::TYPE_PERCENT,
                    ShippingDiscount::DISCOUNT_VALUE => 0.2
                ],
                'shippingCost' => 100.0,
                'expectedDiscount' => 20.0
            ],
            'shipping cost as Price object' => [
                'options' => [
                    ShippingDiscount::DISCOUNT_TYPE => ShippingDiscount::TYPE_AMOUNT,
                    ShippingDiscount::DISCOUNT_VALUE => 100.2,
                    ShippingDiscount::DISCOUNT_CURRENCY => 'EUR'
                ],
                'shippingCost' => Price::create(200.0, 'EUR'),
                'expectedDiscount' => 100.2
            ]
        ];
    }
}
