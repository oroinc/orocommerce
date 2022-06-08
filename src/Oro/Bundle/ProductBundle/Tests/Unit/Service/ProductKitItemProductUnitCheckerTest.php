<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Service;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Service\ProductKitItemProductUnitChecker;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;

class ProductKitItemProductUnitCheckerTest extends \PHPUnit\Framework\TestCase
{
    private ProductKitItemProductUnitChecker $checker;

    protected function setUp(): void
    {
        $this->checker = new ProductKitItemProductUnitChecker();
    }

    public function testIsProductUnitEligibleWhenNoProducts(): void
    {
        self::assertFalse($this->checker->isProductUnitEligible('item', []));
    }

    /**
     * @dataProvider productUnitEligibleDataDataProvider
     *
     * @param string $unitCode
     * @param bool $expected
     */
    public function testIsProductUnitEligible(string $unitCode, bool $expected): void
    {
        $productUnitItem = (new ProductUnit())->setCode('item');
        $productUnitSet = (new ProductUnit())->setCode('set');
        $productUnitEach = (new ProductUnit())->setCode('each');

        $product1UnitPrecisionItem = (new ProductUnitPrecision())->setUnit($productUnitItem);
        $product1UnitPrecisionSet = (new ProductUnitPrecision())->setUnit($productUnitSet);
        $product1 = (new ProductStub())
            ->setId(1)
            ->addUnitPrecision($product1UnitPrecisionItem)
            ->addUnitPrecision($product1UnitPrecisionSet);

        $product2UnitPrecisionEach = (new ProductUnitPrecision())->setUnit($productUnitEach);
        $product2UnitPrecisionSet = (new ProductUnitPrecision())->setUnit($productUnitSet);
        $product2 = (new ProductStub())
            ->setId(2)
            ->addUnitPrecision($product2UnitPrecisionEach)
            ->addUnitPrecision($product2UnitPrecisionSet);

        self::assertSame($expected, $this->checker->isProductUnitEligible($unitCode, [$product1, $product2]));
    }

    public function productUnitEligibleDataDataProvider(): array
    {
        return [
            [
                'unitCode' => 'liter',
                'expected' => false,
            ],
            [
                'unitCode' => 'each',
                'expected' => false,
            ],
            [
                'unitCode' => 'item',
                'expected' => false,
            ],
            [
                'unitCode' => 'set',
                'expected' => true,
            ],
        ];
    }

    public function testGetEligibleProductUnitPrecisionsWhenNoProducts(): void
    {
        self::assertSame([], $this->checker->getEligibleProductUnitPrecisions('set', []));
    }

    /**
     * @dataProvider getEligibleProductUnitPrecisionsDataProvider
     *
     * @param string $unitCode
     * @param Product[] $products
     * @param ProductUnitPrecision[] $expected
     */
    public function testGetEligibleProductUnitPrecisions(string $unitCode, array $products, array $expected): void
    {
        self::assertSame(
            $expected,
            $this->checker->getEligibleProductUnitPrecisions($unitCode, $products)
        );
    }

    public function getEligibleProductUnitPrecisionsDataProvider(): array
    {
        $productUnitItem = (new ProductUnit())->setCode('item');
        $productUnitSet = (new ProductUnit())->setCode('set');
        $productUnitEach = (new ProductUnit())->setCode('each');

        $product1UnitPrecisionItem = (new ProductUnitPrecision())->setUnit($productUnitItem);
        $product1UnitPrecisionSet = (new ProductUnitPrecision())->setUnit($productUnitSet);
        $product1 = (new ProductStub())
            ->setId(1)
            ->addUnitPrecision($product1UnitPrecisionItem)
            ->addUnitPrecision($product1UnitPrecisionSet);

        $product2UnitPrecisionEach = (new ProductUnitPrecision())->setUnit($productUnitEach);
        $product2UnitPrecisionSet = (new ProductUnitPrecision())->setUnit($productUnitSet);
        $product2 = (new ProductStub())
            ->setId(2)
            ->addUnitPrecision($product2UnitPrecisionEach)
            ->addUnitPrecision($product2UnitPrecisionSet);

        return [
            [
                'unitCode' => 'missing',
                'products' => [$product1, $product2],
                'expected' => [],
            ],
            [
                'unitCode' => 'item',
                'products' => [$product1, $product2],
                'expected' => [$product1UnitPrecisionItem],
            ],
            [
                'unitCode' => 'each',
                'products' => [$product1, $product2],
                'expected' => [$product2UnitPrecisionEach],
            ],
            [
                'unitCode' => 'set',
                'products' => [$product1, $product2],
                'expected' => [$product1UnitPrecisionSet, $product2UnitPrecisionSet],
            ],
        ];
    }

    /**
     * @dataProvider getConflictingProductsDataProvider
     *
     * @param string $unitCode
     * @param Product[] $products
     * @param ProductUnitPrecision[] $expected
     */
    public function testGetConflictingProducts(string $unitCode, array $products, array $expected): void
    {
        self::assertSame(
            $expected,
            $this->checker->getConflictingProducts($unitCode, $products)
        );
    }

    public function getConflictingProductsDataProvider(): array
    {
        $productUnitItem = (new ProductUnit())->setCode('item');
        $productUnitSet = (new ProductUnit())->setCode('set');
        $productUnitEach = (new ProductUnit())->setCode('each');

        $product1UnitPrecisionItem = (new ProductUnitPrecision())->setUnit($productUnitItem);
        $product1UnitPrecisionSet = (new ProductUnitPrecision())->setUnit($productUnitSet);
        $product1 = (new ProductStub())
            ->setId(1)
            ->addUnitPrecision($product1UnitPrecisionItem)
            ->addUnitPrecision($product1UnitPrecisionSet);

        $product2UnitPrecisionEach = (new ProductUnitPrecision())->setUnit($productUnitEach);
        $product2UnitPrecisionSet = (new ProductUnitPrecision())->setUnit($productUnitSet);
        $product2 = (new ProductStub())
            ->setId(2)
            ->addUnitPrecision($product2UnitPrecisionEach)
            ->addUnitPrecision($product2UnitPrecisionSet);

        return [
            [
                'unitCode' => 'missing',
                'products' => [$product1, $product2],
                'expected' => [$product1, $product2],
            ],
            [
                'unitCode' => 'item',
                'products' => [$product1, $product2],
                'expected' => [$product2],
            ],
            [
                'unitCode' => 'each',
                'products' => [$product1, $product2],
                'expected' => [$product1],
            ],
            [
                'unitCode' => 'set',
                'products' => [$product1, $product2],
                'expected' => [],
            ],
        ];
    }
}
