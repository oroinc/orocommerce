<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\PricingBundle\Layout\DataProvider\FrontendProductPricesProvider;
use Oro\Bundle\PricingBundle\Layout\DataProvider\ProductUnitsWithoutPricesProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductUnitsWithoutPricesProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var FrontendProductPricesProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $frontendProductPricesProvider;

    /** @var ProductUnitsWithoutPricesProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->frontendProductPricesProvider = $this->createMock(FrontendProductPricesProvider::class);

        $this->provider = new ProductUnitsWithoutPricesProvider($this->frontendProductPricesProvider);
    }

    /**
     * @dataProvider getDataDataProvider
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

    public function getDataDataProvider(): array
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

    private function getProduct(array $product): Product
    {
        $product['unitPrecisions'] = array_map([$this, 'getUnitPrecision'], $product['unitPrecisions']);

        return $this->getEntity(Product::class, $product);
    }

    private function getUnit(array $unit): ProductUnit
    {
        return $this->getEntity(ProductUnit::class, $unit);
    }

    private function getUnitPrecision(array $unitPrecision): ProductUnitPrecision
    {
        $unitPrecision['unit'] = $this->getUnit($unitPrecision['unit']);

        return $this->getEntity(ProductUnitPrecision::class, $unitPrecision);
    }

    /**
     * @return ProductUnit[]
     */
    private function getProductUnit(array $unitPrecision): array
    {
        $productUnit['unit'] = $this->getUnit($unitPrecision['unit']);

        return $productUnit;
    }

    /**
     * @param ProductUnitPrecision[] $expectedData
     * @param ProductUnit[]          $actualData
     */
    private function assertUnitEquals(array $expectedData, array $actualData): void
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
