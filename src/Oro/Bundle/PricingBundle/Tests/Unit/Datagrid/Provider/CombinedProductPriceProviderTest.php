<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Datagrid\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\Datagrid\Provider\CombinedProductPriceProvider;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Tests\Unit\Entity\Repository\Stub\CombinedProductPriceRepository;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatter;
use Oro\Bundle\ProductBundle\Formatter\UnitValueFormatter;
use Oro\Component\Testing\Unit\EntityTrait;

class CombinedProductPriceProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    public function testGetCombinedPricesForProductsByPriceListWithoutPrices()
    {
        list($numberFormatter, $unitLabelFormatter, $unitValueFormatter) = $this->getDependencies();

        $combinedProductPriceProvider = new CombinedProductPriceProvider(
            CombinedProductPriceRepository::withoutPricesForProductsByPriceList(),
            $numberFormatter,
            $unitLabelFormatter,
            $unitValueFormatter
        );

        $combinedPricesForProductsByPriceList = $combinedProductPriceProvider->getCombinedPricesForProductsByPriceList(
            [new ResultRecord([])],
            new CombinedPriceList(),
            'PLN'
        );
        $this->assertEquals([], $combinedPricesForProductsByPriceList);
    }

    /**
     * @dataProvider combinedPricesForProductsByPriceListProvider
     * @param CombinedProductPrice[] $combinedPrices
     * @param array                  $expectedResults
     */
    public function testGetCombinedPricesForProductsByPriceList(array $combinedPrices, array $expectedResults)
    {
        list($numberFormatter, $unitLabelFormatter, $unitValueFormatter) = $this->getDependencies();

        $combinedProductPriceProvider = new CombinedProductPriceProvider(
            CombinedProductPriceRepository::withPricesForProductsByPriceList($combinedPrices),
            $numberFormatter,
            $unitLabelFormatter,
            $unitValueFormatter
        );

        $combinedPricesForProductsByPriceList = $combinedProductPriceProvider->getCombinedPricesForProductsByPriceList(
            [new ResultRecord([])],
            new CombinedPriceList(),
            'PLN'
        );
        $this->assertEquals($expectedResults, $combinedPricesForProductsByPriceList);
    }

    /**
     * @return array
     */
    public function combinedPricesForProductsByPriceListProvider()
    {
        /** @var Product $product */
        $product = $this->getEntity('Oro\Bundle\ProductBundle\Entity\Product', ['id' => 2]);

        $price = new Price();
        $price->setCurrency('EUR');
        $price->setValue(20);

        $cpl1 = new CombinedProductPrice();
        $cpl1->setPrice($price);
        $cpl1->setProduct($product);
        $cpl1->setQuantity(1);
        $cpl1->setUnit((new ProductUnit())->setCode('item'));

        $price = new Price();
        $price->setCurrency('EUR');
        $price->setValue(21);

        $cpl2 = new CombinedProductPrice;
        $cpl2->setPrice($price);
        $cpl2->setProduct($product);
        $cpl2->setQuantity(2);
        $cpl2->setUnit((new ProductUnit())->setCode('item'));

        $cpl3 = new CombinedProductPrice;
        $cpl3->setPrice($price);
        $cpl3->setProduct($product);
        $cpl3->setQuantity(2);
        $cpl3->setUnit((new ProductUnit())->setCode('item'));

        return [
            'valid data' => [
                'combinedPrices'  => [$cpl1, $cpl2],
                'expectedResults' => [
                    2 => [
                        'item_1' => [
                            'price'              => 20,
                            'currency'           => 'EUR',
                            'formatted_price'    => 'EUR20',
                            'unit'               => 'item',
                            'formatted_unit'     => 'item-formatted',
                            'quantity'           => 1,
                            'quantity_with_unit' => '1-item-formatted',
                        ],
                        'item_2' => [
                            'price'              => 21,
                            'currency'           => 'EUR',
                            'formatted_price'    => 'EUR21',
                            'unit'               => 'item',
                            'formatted_unit'     => 'item-formatted',
                            'quantity'           => 2,
                            'quantity_with_unit' => '2-item-formatted',
                        ],
                    ],
                ],
            ],
            'doubled products prices data' => [
                'combinedPrices'  => [$cpl1, $cpl2, $cpl3],
                'expectedResults' => [
                    2 => [
                        'item_1' => [
                            'price'              => 20,
                            'currency'           => 'EUR',
                            'formatted_price'    => 'EUR20',
                            'unit'               => 'item',
                            'formatted_unit'     => 'item-formatted',
                            'quantity'           => 1,
                            'quantity_with_unit' => '1-item-formatted',
                        ],
                        'item_2' => [
                            'price'              => 21,
                            'currency'           => 'EUR',
                            'formatted_price'    => 'EUR21',
                            'unit'               => 'item',
                            'formatted_unit'     => 'item-formatted',
                            'quantity'           => 2,
                            'quantity_with_unit' => '2-item-formatted',
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function getDependencies()
    {
        $numberFormatter = $this->getMockBuilder(NumberFormatter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $unitLabelFormatter =
            $this->getMockBuilder(UnitLabelFormatter::class)
                ->disableOriginalConstructor()
                ->getMock();

        $unitValueFormatter =
            $this->getMockBuilder(UnitValueFormatter::class)
                ->disableOriginalConstructor()
                ->getMock();

        $numberFormatter->expects($this->any())
            ->method('formatCurrency')
            ->willReturnCallback(
                function ($price, $currency) {
                    return $currency . $price;
                }
            );

        $unitLabelFormatter->expects($this->any())
            ->method('format')
            ->willReturnCallback(
                function ($unit) {
                    return $unit . '-formatted';
                }
            );

        $unitValueFormatter->expects($this->any())
            ->method('formatCode')
            ->willReturnCallback(
                function ($quantity, $unit) {
                    return $quantity . '-' . $unit . '-formatted';
                }
            );

        return [$numberFormatter, $unitLabelFormatter, $unitValueFormatter];
    }
}
