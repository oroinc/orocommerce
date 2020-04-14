<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\PricingBundle\Layout\DataProvider\PriceUnitVisibilityProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Visibility\UnitVisibilityInterface;

class PriceUnitVisibilityProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UnitVisibilityInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $unitVisibility;

    /**
     * @var PriceUnitVisibilityProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->unitVisibility = $this->createMock(UnitVisibilityInterface::class);
        $this->provider = new PriceUnitVisibilityProvider($this->unitVisibility);
    }

    public function testIsPriceUnitsVisibleByProduct()
    {
        $unitPrecision1 = $this->createUnitPrecisionMock('item');

        $product = $this->createProductMock([$unitPrecision1]);

        $this->unitVisibility->expects($this->once())
            ->method('isUnitCodeVisible')
            ->with('item')
            ->willReturn(false);

        $this->assertFalse($this->provider->isPriceUnitsVisibleByProduct($product));
    }

    public function testIsPriceUnitsVisibleByProductFalse()
    {
        $unitPrecision1 = $this->createUnitPrecisionMock('item');
        $unitPrecision2 = $this->createUnitPrecisionMock('set');

        $product = $this->createProductMock([$unitPrecision1, $unitPrecision2]);

        $this->unitVisibility->expects($this->at(0))
            ->method('isUnitCodeVisible')
            ->with('item')
            ->willReturn(true);

        $this->unitVisibility->expects($this->at(1))
            ->method('isUnitCodeVisible')
            ->with('set')
            ->willReturn(false);

        $this->assertTrue($this->provider->isPriceUnitsVisibleByProduct($product));
    }

    public function testGetPriceUnitsVisibilityByProducts()
    {
        $unitPrecision1 = $this->createUnitPrecisionMock('item');
        $unitPrecision2 = $this->createUnitPrecisionMock('set');

        $product1 = $this->createProductMock([$unitPrecision1]);
        $product1->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $product2 = $this->createProductMock([$unitPrecision2]);
        $product2->expects($this->once())
            ->method('getId')
            ->willReturn(2);

        $this->unitVisibility->expects($this->at(0))
            ->method('isUnitCodeVisible')
            ->with('item')
            ->willReturn(true);

        $this->unitVisibility->expects($this->at(1))
            ->method('isUnitCodeVisible')
            ->with('set')
            ->willReturn(false);

        $this->assertEquals([
            1 => true,
            2 => false,
        ], $this->provider->getPriceUnitsVisibilityByProducts([$product1, $product2]));
    }

    /**
     * @param string $unitCode
     * @return ProductUnitPrecision|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createUnitPrecisionMock($unitCode)
    {
        $unit = $this->createMock(ProductUnit::class);
        $unit->expects($this->once())
            ->method('getCode')
            ->willReturn($unitCode);

        $unitPrecision = $this->createMock(ProductUnitPrecision::class);
        $unitPrecision->expects($this->once())
            ->method('isSell')
            ->willReturn(true);

        $unitPrecision->expects($this->once())
            ->method('getUnit')
            ->willReturn($unit);

        return $unitPrecision;
    }

    /**
     * @param array $unitPrecisions
     * @return Product|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createProductMock(array $unitPrecisions)
    {
        $product = $this->createMock(Product::class);

        $product->expects($this->once())
            ->method('getUnitPrecisions')
            ->willReturn(new ArrayCollection($unitPrecisions));

        return $product;
    }
}
