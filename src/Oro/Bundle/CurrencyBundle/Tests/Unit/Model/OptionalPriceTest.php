<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Model;

use Oro\Component\Testing\Unit\EntityTestCase;

use Oro\Bundle\CurrencyBundle\Model\OptionalPrice;

class OptionalPriceTest extends EntityTestCase
{
    public function testAccessors()
    {
        $properties = [
            ['value', 10],
            ['currency', 'USD'],
        ];

        static::assertPropertyAccessors(new OptionalPrice(), $properties);
    }

    public function testDefaultCreate()
    {
        $this->assertInstanceOf(
            'Oro\Bundle\CurrencyBundle\Model\OptionalPrice',
            OptionalPrice::create()
        );
    }

    /**
     * @param float $price
     * @param string $currency
     * @param OptionalPrice $expected
     * @dataProvider createProvider
     */
    public function testCreate($price, $currency, $expected)
    {
        $price = OptionalPrice::create($price, $currency);

        $this->assertInstanceOf(
            'Oro\Bundle\CurrencyBundle\Model\OptionalPrice',
            $price
        );

        $this->assertEquals($expected, $price);
    }

    public function createProvider()
    {
        return [
            'full data' => [
                'price'     => 10,
                'currency'  => 'USD',
                'expected'  => (new OptionalPrice())->setValue(10)->setCurrency('USD'),
            ],
            'empty price' => [
                'price'     => null,
                'currency'  => 'USD',
                'expected'  => (new OptionalPrice())->setCurrency('USD'),
            ],
            'empty currency' => [
                'price'     => 10,
                'currency'  => null,
                'expected'  => (new OptionalPrice())->setValue(10),
            ],
            'empty data' => [
                'price'     => null,
                'currency'  => null,
                'expected'  => new OptionalPrice(),
            ],
        ];
    }
}
