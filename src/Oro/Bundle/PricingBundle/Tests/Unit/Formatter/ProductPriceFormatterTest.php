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
use Oro\Component\Testing\ReflectionUtil;

class ProductPriceFormatterTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductPriceFormatter */
    private $formatter;

    protected function setUp(): void
    {
        $numberFormatter = $this->createMock(NumberFormatter::class);
        $numberFormatter->expects($this->any())
            ->method('formatCurrency')
            ->willReturnCallback(function ($price, $currencyIsoCode) {
                return sprintf('%.2f %s formatted_price', $price, $currencyIsoCode);
            });

        $unitLabelFormatter = $this->createMock(UnitLabelFormatterInterface::class);
        $unitLabelFormatter->expects($this->any())
            ->method('format')
            ->willReturnCallback(function ($unit) {
                return sprintf('%s formatted_unit', $unit);
            });

        $unitValueFormatter = $this->createMock(UnitValueFormatterInterface::class);
        $unitValueFormatter->expects($this->any())
            ->method('formatCode')
            ->willReturnCallback(function ($quantity, $unit) {
                return sprintf('%d %s quantity_with_unit', $quantity, $unit);
            });

        $this->formatter = new ProductPriceFormatter(
            $numberFormatter,
            $unitLabelFormatter,
            $unitValueFormatter
        );
    }

    private function getProduct(int $id): Product
    {
        $product = new Product();
        ReflectionUtil::setId($product, $id);

        return $product;
    }

    private function getProductUnit(string $unitCode): ProductUnit
    {
        $unit = new ProductUnit();
        $unit->setCode($unitCode);

        return $unit;
    }

    /**
     * @dataProvider formatProductsDataProvider
     */
    public function testFormatProducts(array $products, array $expectedData)
    {
        $this->assertEquals($expectedData, $this->formatter->formatProducts($products));
    }

    public function formatProductsDataProvider(): array
    {
        return [
            [
                'products' => [
                    1 => [
                        'item' => [
                            new ProductPriceDTO(
                                $this->getProduct(1),
                                Price::create(14.45, 'USD'),
                                1,
                                $this->getProductUnit('item')
                            )
                        ],
                        'set' => [
                            new ProductPriceDTO(
                                $this->getProduct(1),
                                Price::create(12.45, 'EUR'),
                                10,
                                $this->getProductUnit('set')
                            )
                        ],
                    ],
                    2 => [
                        'kg' => [
                            new ProductPriceDTO(
                                $this->getProduct(2),
                                Price::create(10.22, 'USD'),
                                1,
                                $this->getProductUnit('kg')
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
