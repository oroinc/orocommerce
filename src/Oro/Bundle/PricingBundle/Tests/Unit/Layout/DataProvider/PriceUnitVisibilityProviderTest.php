<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\PricingBundle\Layout\DataProvider\PriceUnitVisibilityProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\ProductBundle\Visibility\UnitVisibilityInterface;

class PriceUnitVisibilityProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var UnitVisibilityInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $unitVisibility;

    /** @var PriceUnitVisibilityProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->unitVisibility = $this->createMock(UnitVisibilityInterface::class);

        $this->provider = new PriceUnitVisibilityProvider($this->unitVisibility);
    }

    private function getProductView(int $id, array $units): ProductView
    {
        $product = new ProductView();
        $product->set('id', $id);
        $product->set('product_units', array_fill_keys($units, 0));

        return $product;
    }

    private function getProduct(array $unitPrecisions): Product
    {
        $product = new Product();
        foreach ($unitPrecisions as $unitPrecision) {
            $product->addUnitPrecision($unitPrecision);
        }

        return $product;
    }

    private function getUnitPrecision(string $unitCode, bool $sell): ProductUnitPrecision
    {
        $unit = new ProductUnit();
        $unit->setCode($unitCode);

        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision->setSell($sell);
        $unitPrecision->setUnit($unit);

        return $unitPrecision;
    }

    public function testIsPriceUnitsVisibleByProduct()
    {
        $unitPrecision1 = $this->getUnitPrecision('item', true);
        $unitPrecision2 = $this->getUnitPrecision('set', true);

        $product = $this->getProduct([$unitPrecision1, $unitPrecision2]);

        $this->unitVisibility->expects($this->exactly(2))
            ->method('isUnitCodeVisible')
            ->willReturnMap([
                ['item', false],
                ['set', true]
            ]);

        $this->assertTrue($this->provider->isPriceUnitsVisibleByProduct($product));
    }

    public function testIsPriceUnitsVisibleByProductWithNotSellUnit()
    {
        $unitPrecision1 = $this->getUnitPrecision('item', false);
        $unitPrecision2 = $this->getUnitPrecision('set', true);

        $product = $this->getProduct([$unitPrecision1, $unitPrecision2]);

        $this->unitVisibility->expects($this->once())
            ->method('isUnitCodeVisible')
            ->with('set')
            ->willReturn(true);

        $this->assertTrue($this->provider->isPriceUnitsVisibleByProduct($product));
    }

    public function testIsPriceUnitsVisibleByProductWhenNoVisibleUnits()
    {
        $unitPrecision1 = $this->getUnitPrecision('item', true);
        $unitPrecision2 = $this->getUnitPrecision('set', true);

        $product = $this->getProduct([$unitPrecision1, $unitPrecision2]);

        $this->unitVisibility->expects($this->exactly(2))
            ->method('isUnitCodeVisible')
            ->willReturn(false);

        $this->assertFalse($this->provider->isPriceUnitsVisibleByProduct($product));
    }

    public function testIsPriceUnitsVisibleByProductView()
    {
        $product = $this->getProductView(1, ['item', 'set']);

        $this->unitVisibility->expects($this->exactly(2))
            ->method('isUnitCodeVisible')
            ->willReturnMap([
                ['item', false],
                ['set', true]
            ]);

        $this->assertTrue($this->provider->isPriceUnitsVisibleByProduct($product));
    }

    public function testIsPriceUnitsVisibleByProductViewWhenNoVisibleUnits()
    {
        $product = $this->getProductView(1, ['item', 'set']);

        $this->unitVisibility->expects($this->exactly(2))
            ->method('isUnitCodeVisible')
            ->willReturn(false);

        $this->assertFalse($this->provider->isPriceUnitsVisibleByProduct($product));
    }

    public function testGetPriceUnitsVisibilityByProducts()
    {
        $product1 = $this->getProductView(1, ['item', 'set']);
        $product2 = $this->getProductView(2, ['each', 'item']);

        $this->unitVisibility->expects($this->exactly(4))
            ->method('isUnitCodeVisible')
            ->willReturnMap([
                ['item', false],
                ['set', true],
                ['each', false]
            ]);

        $this->assertSame(
            [1 => true, 2 => false],
            $this->provider->getPriceUnitsVisibilityByProducts([$product1, $product2])
        );
    }
}
