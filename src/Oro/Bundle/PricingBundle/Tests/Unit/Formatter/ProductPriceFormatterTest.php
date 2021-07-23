<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Formatter;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\Formatter\ProductPriceFormatter;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\ProductBundle\Formatter\UnitValueFormatterInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductPriceFormatterTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ProductPriceFormatter
     */
    protected $formatter;

    /**
     * @var NumberFormatter|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $numberFormatter;

    /**
     * @var UnitLabelFormatterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $unitLabelFormatter;

    /**
     * @var UnitValueFormatterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $unitValueFormatter;

    protected function setUp(): void
    {
        $this->numberFormatter = $this->getMockBuilder(NumberFormatter::class)
            ->disableOriginalConstructor()->getMock();
        $this->numberFormatter->expects($this->any())
            ->method('formatCurrency')
            ->will($this->returnCallback(function ($price, $currencyIsoCode) {
                return sprintf('%.2f %s formatted_price', $price, $currencyIsoCode);
            }));
        $this->unitLabelFormatter = $this
            ->getMockBuilder(UnitLabelFormatterInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->unitLabelFormatter->expects($this->any())
            ->method('format')
            ->will($this->returnCallback(function ($unit) {
                return sprintf('%s formatted_unit', $unit);
            }));
        $this->unitValueFormatter = $this
            ->getMockBuilder(UnitValueFormatterInterface::class)
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
                            new ProductPriceDTO(
                                $this->getEntity(Product::class, ['id' => 1]),
                                Price::create(14.45, 'USD'),
                                1,
                                $this->getEntity(ProductUnit::class, ['code' => 'item'])
                            )
                        ],
                        'set' => [
                            new ProductPriceDTO(
                                $this->getEntity(Product::class, ['id' => 1]),
                                Price::create(12.45, 'EUR'),
                                10,
                                $this->getEntity(ProductUnit::class, ['code' => 'set'])
                            )
                        ],
                    ],
                    2 => [
                        'kg' => [
                            new ProductPriceDTO(
                                $this->getEntity(Product::class, ['id' => 2]),
                                Price::create(10.22, 'USD'),
                                1,
                                $this->getEntity(ProductUnit::class, ['code' => 'kg'])
                            )
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
}
