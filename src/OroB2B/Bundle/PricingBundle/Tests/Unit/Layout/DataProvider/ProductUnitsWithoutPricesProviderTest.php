<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Layout\DataProvider;

use Oro\Component\Layout\LayoutContext;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\PricingBundle\Layout\DataProvider\FrontendProductPricesProvider;
use OroB2B\Bundle\PricingBundle\Layout\DataProvider\ProductUnitsWithoutPricesProvider;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class ProductUnitsWithoutPricesProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var FrontendProductPricesProvider
     */
    protected $provider;

    /**
     * @var FrontendProductPricesProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $frontendProductPricesProvider;

    public function setUp()
    {
        $this->frontendProductPricesProvider = $this->getMockBuilder(
            'OroB2B\Bundle\PricingBundle\Layout\DataProvider\FrontendProductPricesProvider'
        )->disableOriginalConstructor()->getMock();

        $this->provider = new ProductUnitsWithoutPricesProvider($this->frontendProductPricesProvider);
    }

    /**
     * @dataProvider getDataDataProvider
     * @param array $product
     * @param array $unitPrecisionsWithPrices
     * @param array $expectedData
     */
    public function testGetData(array $product, array $unitPrecisionsWithPrices, array $expectedData)
    {
        $product = $this->getProduct($product);

        $context = new LayoutContext();
        $context->data()->set('product', null, $product);

        $this->frontendProductPricesProvider->expects($this->once())
            ->method('getData')
            ->willReturn(array_map([$this, 'getUnitPrecision'], $unitPrecisionsWithPrices));

        $actual = $this->provider->getData($context);

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
        return $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', $product);
    }

    /**
     * @param array $unit
     * @return ProductUnit
     */
    protected function getUnit(array $unit)
    {
        return $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', $unit);
    }

    /**
     * @param array $unitPrecision
     * @return ProductUnitPrecision
     */
    protected function getUnitPrecision(array $unitPrecision)
    {
        $unitPrecision['unit'] = $this->getUnit($unitPrecision['unit']);
        return $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision', $unitPrecision);
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
