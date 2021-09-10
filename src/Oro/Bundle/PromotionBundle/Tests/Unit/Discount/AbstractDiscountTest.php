<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\PromotionBundle\Discount\AbstractDiscount;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Discount\Exception\ConfiguredException;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class AbstractDiscountTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DiscountInterface
     */
    private $discount;

    protected function setUp(): void
    {
        $this->discount = $this->getMockForAbstractClass(AbstractDiscount::class);
    }

    public function testConfigureDefaultOptions()
    {
        $options = [];

        $this->discount->configure($options);
        $this->assertSame(AbstractDiscount::TYPE_PERCENT, $this->discount->getDiscountType());
        $this->assertSame(0.0, $this->discount->getDiscountValue());
    }

    public function testConfigureFixedAmount()
    {
        $options = [
            AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
            AbstractDiscount::DISCOUNT_VALUE => 100.2,
            AbstractDiscount::DISCOUNT_CURRENCY => 'EUR'
        ];

        $this->discount->configure($options);
        $this->assertSame($options[AbstractDiscount::DISCOUNT_TYPE], $this->discount->getDiscountType());
        $this->assertSame($options[AbstractDiscount::DISCOUNT_VALUE], $this->discount->getDiscountValue());
        $this->assertSame($options[AbstractDiscount::DISCOUNT_CURRENCY], $this->discount->getDiscountCurrency());
    }

    public function testConfigurePercent()
    {
        $options = [
            AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_PERCENT,
            AbstractDiscount::DISCOUNT_VALUE => 0.3
        ];

        $this->discount->configure($options);
        $this->assertSame($options[AbstractDiscount::DISCOUNT_TYPE], $this->discount->getDiscountType());
        $this->assertSame($options[AbstractDiscount::DISCOUNT_VALUE], $this->discount->getDiscountValue());
    }

    public function testSetGetMatchingProducts()
    {
        $products = [$this->createMock(Product::class)];
        $this->discount->setMatchingProducts($products);
        $this->assertSame($products, $this->discount->getMatchingProducts());
    }

    public function testDoubleConfiguration()
    {
        $this->expectException(ConfiguredException::class);
        $this->discount->configure([]);
        $this->discount->configure([]);
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
            'invalid DISCOUNT_TYPE type' => [
                [
                    AbstractDiscount::DISCOUNT_TYPE => []
                ]
            ],
            'invalid DISCOUNT_VALUE type' => [
                [
                    AbstractDiscount::DISCOUNT_VALUE => 'abc'
                ]
            ],
            'invalid DISCOUNT_CURRENCY type' => [
                [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    AbstractDiscount::DISCOUNT_CURRENCY => 100
                ]
            ],
            'invalid DISCOUNT_CURRENCY code length' => [
                [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'ABCD'
                ]
            ],
        ];
    }
}
