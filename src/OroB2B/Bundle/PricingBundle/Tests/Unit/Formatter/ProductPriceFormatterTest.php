<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Formatter;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

use OroB2B\Bundle\PricingBundle\Formatter\ProductPriceFormatter;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter;

class ProductPriceFormatterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductPriceFormatter
     */
    protected $formatter;

    /**
     * @var NumberFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $numberFormatter;

    /**
     * @var ProductUnitLabelFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $unitLabelFormatter;

    /**
     * @var ProductUnitValueFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $unitValueFormatter;

    protected function setUp()
    {
        $this->numberFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\NumberFormatter')
            ->disableOriginalConstructor()->getMock();
        $this->numberFormatter->expects($this->any())
            ->method('formatCurrency')
            ->will($this->returnCallback(function ($price, $currencyIsoCode) {
                return sprintf('%.2f %s formatted_price', $price, $currencyIsoCode);
            }));
        $this->unitLabelFormatter = $this
            ->getMockBuilder('OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter')
            ->disableOriginalConstructor()->getMock();
        $this->unitLabelFormatter->expects($this->any())
            ->method('format')
            ->will($this->returnCallback(function ($unit) {
                return sprintf('%s formatted_unit', $unit);
            }));
        $this->unitValueFormatter = $this
            ->getMockBuilder('OroB2B\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter')
            ->disableOriginalConstructor()->getMock();
        $this->unitValueFormatter->expects($this->any())
            ->method('formatCode')
            ->will($this->returnCallback(function ($quantity, $unit) {
                return sprintf('%d %s quantity_with_unit', $quantity, $unit);
            }));
        $this->formatter = new ProductPriceFormatter(
            $this->numberFormatter,
            $this->unitLabelFormatter,
            $this->unitValueFormatter
        );
    }

    /**
     * @dataProvider formatProductsDataProvider
     * @param array $products
     * @param array $expectedData
     */
    public function testFormatProducts(array $products, array $expectedData)
    {
        $this->assertEquals($expectedData, $this->formatter->formatProducts($products));
    }

    /**
     * @return array
     */
    public function formatProductsDataProvider()
    {
        return [
            [
                'products' => [
                    1 => [
                        'item' => [
                            [
                                'price' => 14.45,
                                'currency' => 'USD',
                                'qty' => 1,
                            ]
                        ],
                        'set' => [
                            [
                                'price' => 12.45,
                                'currency' => 'EUR',
                                'qty' => 10,
                            ]
                        ],
                    ],
                    2 => [
                        'kg' => [
                            [
                                'price' => 10.22,
                                'currency' => 'USD',
                                'qty' => 1,
                            ]
                        ],
                    ]
                ],
                'expectedData' => [
                    1 => [
                        'item_1' => [
                            'price' => 14.45,
                            'currency' => 'USD',
                            'formatted_price' => '14.45 USD formatted_price',
                            'unit' => 'item',
                            'formatted_unit' => 'item formatted_unit',
                            'quantity' => 1,
                            'quantity_with_unit' => '1 item quantity_with_unit'
                        ],
                        'set_10' => [
                            'price' => 12.45,
                            'currency' => 'EUR',
                            'formatted_price' => '12.45 EUR formatted_price',
                            'unit' => 'set',
                            'formatted_unit' => 'set formatted_unit',
                            'quantity' => 10,
                            'quantity_with_unit' => '10 set quantity_with_unit'
                        ]
                    ],
                    2 => [
                        'kg_1' => [
                            'price' => 10.22,
                            'currency' => 'USD',
                            'formatted_price' => '10.22 USD formatted_price',
                            'unit' => 'kg',
                            'formatted_unit' => 'kg formatted_unit',
                            'quantity' => 1,
                            'quantity_with_unit' => '1 kg quantity_with_unit'
                        ],
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider formatProductUnitsDataProvider
     * @param array $productUnits
     * @param array $expectedData
     */
    public function testFormatProductUnits(array $productUnits, array $expectedData)
    {
        $this->assertEquals($expectedData, $this->formatter->formatProductUnits($productUnits));
    }

    /**
     * @return array
     */
    public function formatProductUnitsDataProvider()
    {
        return [
            [
                'productUnits' => [
                    'item' => [
                        [
                            'price' => 14.45,
                            'currency' => 'USD',
                            'qty' => 1,
                        ]
                    ],
                    'set' => [
                        [
                            'price' => 12.45,
                            'currency' => 'EUR',
                            'qty' => 10,
                        ]
                    ],
                ],
                'expectedData' => [
                    'item_1' => [
                        'price' => 14.45,
                        'currency' => 'USD',
                        'formatted_price' => '14.45 USD formatted_price',
                        'unit' => 'item',
                        'formatted_unit' => 'item formatted_unit',
                        'quantity' => 1,
                        'quantity_with_unit' => '1 item quantity_with_unit'
                    ],
                    'set_10' => [
                        'price' => 12.45,
                        'currency' => 'EUR',
                        'formatted_price' => '12.45 EUR formatted_price',
                        'unit' => 'set',
                        'formatted_unit' => 'set formatted_unit',
                        'quantity' => 10,
                        'quantity_with_unit' => '10 set quantity_with_unit'
                    ]
                ]
            ]
        ];
    }
}
