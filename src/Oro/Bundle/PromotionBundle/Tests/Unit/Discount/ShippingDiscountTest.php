<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount;

use Oro\Bundle\OrderBundle\Model\ShippingAwareInterface;
use Oro\Bundle\PromotionBundle\Discount\AbstractDiscount;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\ShippingDiscount;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class ShippingDiscountTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ShippingDiscount
     */
    private $discount;

    protected function setUp(): void
    {
        $this->discount = new ShippingDiscount();
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
            'invalid SHIPPING_OPTIONS type' => [
                [
                    ShippingDiscount::SHIPPING_OPTIONS => 1.0,
                ],
            ],
        ];
    }

    public function testApply()
    {
        $discountContext = new DiscountContext();

        $options = [
            AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_PERCENT,
            AbstractDiscount::DISCOUNT_VALUE => 0.2,
        ];
        $this->discount->configure($options);

        $this->discount->apply($discountContext);
        $this->assertCount(1, $discountContext->getShippingDiscounts());
        $this->assertEquals($this->discount, $discountContext->getShippingDiscounts()[0]);
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
     * @param float $shippingCost
     * @param float $expectedDiscount
     */
    public function testCalculate(array $options, $shippingCost, $expectedDiscount)
    {
        $entity = $this->createMock(ShippingAwareInterface::class);
        $entity->expects($this->any())
            ->method('getShippingCost')
            ->willReturn($shippingCost);

        $this->discount->configure($options);

        $this->assertSame($expectedDiscount, $this->discount->calculate($entity));
    }

    public function calculateDataProvider(): array
    {
        return [
            'fixed amount > shipping cost' => [
                'options' => [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    AbstractDiscount::DISCOUNT_VALUE => 100.2,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'EUR'
                ],
                'shippingCost' => 100.0,
                'expectedDiscount' => 100.0
            ],
            'fixed amount < shipping cost' => [
                'options' => [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    AbstractDiscount::DISCOUNT_VALUE => 100.2,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'EUR'
                ],
                'shippingCost' => 200.0,
                'expectedDiscount' => 100.2
            ],
            'percent' => [
                'options' => [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_PERCENT,
                    AbstractDiscount::DISCOUNT_VALUE => 0.2
                ],
                'shippingCost' => 100.0,
                'expectedDiscount' => 20.0
            ]
        ];
    }
}
