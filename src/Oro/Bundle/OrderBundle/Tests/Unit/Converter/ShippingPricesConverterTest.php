<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Converter;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Converter\ShippingPricesConverter;

class ShippingPricesConverterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider convertPricesToArrayProvider
     * @param array $inputData
     * @param array $expectedResults
     */
    public function testConvertPricesToArray($inputData, $expectedResults)
    {
        $converter = new ShippingPricesConverter();
        static::assertEquals($expectedResults, $converter->convertPricesToArray($inputData));
    }

    public function convertPricesToArrayProvider()
    {
        return [
            [
                'inputData' => [
                    'method1' => [
                        'types' => [
                            'type1' => ['price' => Price::create(10, 'USD')],
                            'type2' => ['price' => Price::create(20, 'USD')],
                        ]
                    ]
                ],
                'expectedResults' => [
                    'method1' => [
                        'types' => [
                            'type1' => [
                                'price' => ['value' => 10, 'currency' => 'USD'],
                            ],
                            'type2' => [
                                'price' => ['value' => 20, 'currency' => 'USD'],
                            ]
                        ]
                    ]
                ],
            ],
        ];
    }
}
