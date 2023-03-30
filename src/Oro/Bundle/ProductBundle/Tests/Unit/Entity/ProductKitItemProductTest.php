<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Entity;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ProductKitItemProductTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $properties = [
            ['id', 123],
            ['kitItem', new ProductKitItem()],
            ['product', new Product()],
            ['sortOrder', 42],
        ];

        self::assertPropertyAccessors(new ProductKitItemProduct(), $properties);
    }

    public function testUpdateProductUnitPrecisionWhenKitItemHasProductUnit(): void
    {
        $productUnit = (new ProductUnit())->setCode('item');
        $product1UnitPrecision = (new ProductUnitPrecision())->setUnit($productUnit);
        $product1 = (new ProductStub())
            ->setId(424242)
            ->addUnitPrecision($product1UnitPrecision);

        $productKitItemProduct = (new ProductKitItemProduct())
            ->setProduct($product1);
        (new ProductKitItemStub(42))
            ->setProductUnit($productUnit)
            ->addKitItemProduct($productKitItemProduct);

        self::assertNull($productKitItemProduct->getProductUnitPrecision());

        $productKitItemProduct->updateProductUnitPrecision();

        self::assertSame($product1UnitPrecision, $productKitItemProduct->getProductUnitPrecision());
    }

    public function testUpdateProductUnitPrecisionWhenKitItemHasNoProductUnit(): void
    {
        $productUnit = (new ProductUnit())->setCode('item');
        $product1UnitPrecision = (new ProductUnitPrecision())->setUnit($productUnit);
        $product1 = (new ProductStub())
            ->setId(424242)
            ->addUnitPrecision($product1UnitPrecision);

        $productKitItemProduct = (new ProductKitItemProduct())
            ->setProduct($product1)
            ->setProductUnitPrecision($product1UnitPrecision);
        (new ProductKitItemStub(42))
            ->addKitItemProduct($productKitItemProduct);

        self::assertSame($product1UnitPrecision, $productKitItemProduct->getProductUnitPrecision());

        $productKitItemProduct->updateProductUnitPrecision();

        self::assertNull($productKitItemProduct->getProductUnitPrecision());
    }

    public function testUpdateProductUnitPrecisionWhenKitItemHasProductUnitButUnitPrecisionIsMissing(): void
    {
        $productUnit = (new ProductUnit())->setCode('item');
        $product1UnitPrecision = (new ProductUnitPrecision())->setUnit($productUnit);
        $product1 = (new ProductStub())
            ->setId(424242)
            ->addUnitPrecision($product1UnitPrecision);

        $productKitItemProduct = (new ProductKitItemProduct())
            ->setProduct($product1)
            ->setProductUnitPrecision($product1UnitPrecision);
        $newProductUnit = (new ProductUnit())->setCode('each');
        (new ProductKitItemStub(42))
            ->setProductUnit($newProductUnit)
            ->addKitItemProduct($productKitItemProduct);

        self::assertSame($product1UnitPrecision, $productKitItemProduct->getProductUnitPrecision());

        $productKitItemProduct->updateProductUnitPrecision();

        self::assertNull($productKitItemProduct->getProductUnitPrecision());
    }

    public function testUpdateProductUnitPrecisionWithExplicitUnitCode(): void
    {
        $productUnit = (new ProductUnit())->setCode('item');
        $product1UnitPrecision = (new ProductUnitPrecision())->setUnit($productUnit);
        $product1 = (new ProductStub())
            ->setId(424242)
            ->addUnitPrecision($product1UnitPrecision);

        $productKitItemProduct = (new ProductKitItemProduct())
            ->setProduct($product1);

        self::assertNull($productKitItemProduct->getProductUnitPrecision());

        $productKitItemProduct->updateProductUnitPrecision($productUnit->getCode());

        self::assertSame($product1UnitPrecision, $productKitItemProduct->getProductUnitPrecision());
    }

    /**
     * @dataProvider updateProductUnitPrecisionWithExplicitUnitCodeDataProvider
     */
    public function testUpdateProductUnitPrecisionWithExplicitUnitCodeDoesNotMatchUnitPrecision(string $unitCode): void
    {
        $productUnit = (new ProductUnit())->setCode('item');
        $product1UnitPrecision = (new ProductUnitPrecision())->setUnit($productUnit);
        $product1 = (new ProductStub())
            ->setId(424242)
            ->addUnitPrecision($product1UnitPrecision);

        $productKitItemProduct = (new ProductKitItemProduct())
            ->setProductUnitPrecision($product1UnitPrecision)
            ->setProduct($product1);

        self::assertSame($product1UnitPrecision, $productKitItemProduct->getProductUnitPrecision());

        $productKitItemProduct->updateProductUnitPrecision($unitCode);

        self::assertNull($productKitItemProduct->getProductUnitPrecision());
    }

    public function updateProductUnitPrecisionWithExplicitUnitCodeDataProvider(): array
    {
        return [
            ['unitCode' => ''],
            ['unitCode' => 'each']
        ];
    }
}
