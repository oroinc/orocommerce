<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener\Doctrine;

use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\EventListener\Doctrine\ProductKitItemProductUnitPrecisionDoctrineListener;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;

class ProductKitItemProductUnitPrecisionDoctrineListenerTest extends \PHPUnit\Framework\TestCase
{
    private ProductKitItemProductUnitPrecisionDoctrineListener $listener;

    protected function setUp(): void
    {
        $this->listener = new ProductKitItemProductUnitPrecisionDoctrineListener();
    }

    public function testPreUpdateWhenKitItemHasProductUnit(): void
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

        $this->listener->preUpdate($productKitItemProduct);

        self::assertSame($product1UnitPrecision, $productKitItemProduct->getProductUnitPrecision());
    }

    public function testPreUpdateWhenKitItemHasNoProductUnit(): void
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

        $this->listener->preUpdate($productKitItemProduct);

        self::assertNull($productKitItemProduct->getProductUnitPrecision());
    }

    public function testPreUpdateWhenKitItemHasProductUnitButUnitPrecisionIsMissing(): void
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

        $this->listener->preUpdate($productKitItemProduct);

        self::assertNull($productKitItemProduct->getProductUnitPrecision());
    }
}
