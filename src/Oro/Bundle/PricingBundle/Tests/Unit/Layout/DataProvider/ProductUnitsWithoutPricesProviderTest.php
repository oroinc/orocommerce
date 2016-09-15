<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Layout\DataProvider;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\PricingBundle\Layout\DataProvider\FrontendProductPricesProvider;
use Oro\Bundle\PricingBundle\Layout\DataProvider\ProductUnitsWithoutPricesProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class ProductUnitsWithoutPricesProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ProductUnitsWithoutPricesProvider
     */
    protected $provider;

    /**
     * @var FrontendProductPricesProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $frontendProductPricesProvider;

    public function setUp()
    {
        $this->frontendProductPricesProvider = $this->getMockBuilder(
            'Oro\Bundle\PricingBundle\Layout\DataProvider\FrontendProductPricesProvider'
        )->disableOriginalConstructor()->getMock();

        $this->provider = new ProductUnitsWithoutPricesProvider($this->frontendProductPricesProvider);
    }

    /**
     * @dataProvider getDataDataProvider
     * @param array $product
     * @param array $unitPrecisionsWithPrices
     * @param array $expectedData
     */
    public function testGetByProduct(array $product, array $unitPrecisionsWithPrices, array $expectedData)
    {
        $product = $this->getProduct($product);

        $this->frontendProductPricesProvider->expects($this->once())
            ->method('getByProduct')
            ->willReturn(array_map([$this, 'getProductUnit'], $unitPrecisionsWithPrices));

        $actual = $this->provider->getProductUnits($product);

        $this->assertUnitEquals($expectedData, $actual);
    }

    /**
     * @return array
     */
    public function getDataDataProvider()
    {
        return [
            [
                'product' => [
                    'id' => 1,
                    'unitPrecisions' => [
                        ['unit' => ['code' => 'item'], 'sell' => true],
                        ['unit' => ['code' => 'kg'], 'sell' => false],
                    ],
                ],
                'unitPrecisionsWithPrices' => [
                    ['unit' => ['code' => 'item'], 'sell' => true],
                    ['unit' => ['code' => 'kg'], 'sell' => false],
                ],
                'expectedData' => [],
            ],
            [
                'product' => [
                    'id' => 1,
                    'unitPrecisions' => [
                        ['unit' => ['code' => 'item'],'sell' => true],
                        ['unit' => ['code' => 'kg'], 'sell' => true],
                        ['unit' => ['code' => 'set'], 'sell' => false],
                    ],
                ],
                'unitPrecisionsWithPrices' => [
                    ['unit' => ['code' => 'item'],'sell' => true],
                ],
                'expectedData' => [
                    ['unit' => ['code' => 'kg']]
                ],
            ],
        ];
    }

    /**
     * @param array $product
     * @return Product
     */
    protected function getProduct(array $product)
    {
        $product['unitPrecisions'] = array_map([$this, 'getUnitPrecision'], $product['unitPrecisions']);
        return $this->getEntity('Oro\Bundle\ProductBundle\Entity\Product', $product);
    }

    /**
     * @param array $unit
     * @return ProductUnit
     */
    protected function getUnit(array $unit)
    {
        return $this->getEntity('Oro\Bundle\ProductBundle\Entity\ProductUnit', $unit);
    }

    /**
     * @param array $unitPrecision
     * @return ProductUnitPrecision
     */
    protected function getUnitPrecision(array $unitPrecision)
    {
        $unitPrecision['unit'] = $this->getUnit($unitPrecision['unit']);
        return $this->getEntity('Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision', $unitPrecision);
    }

    /**
     * @param array $unitPrecision
     * @return ProductUnit[]
     */
    protected function getProductUnit(array $unitPrecision)
    {
        $productUnit['unit'] = $this->getUnit($unitPrecision['unit']);

        return $productUnit;
    }

    /**
     * @param array|ProductUnitPrecision[] $expectedData
     * @param array|ProductUnitPrecision[] $actualData
     */
    protected function assertUnitEquals(array $expectedData, array $actualData)
    {
        $this->assertSameSize($expectedData, $actualData);
        foreach ($expectedData as $unitPrecision) {
            $found = false;
            foreach ($actualData as $actualUnits) {
                if ($actualUnits->getCode() === $unitPrecision['unit']['code']) {
                    $found = true;
                }
            }
            $this->assertTrue($found);
        }
    }
}
