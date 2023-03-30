<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\EventListener\Doctrine\ProductKitItemProductUnitDoctrineListener;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;

class ProductKitItemProductUnitDoctrineListenerTest extends \PHPUnit\Framework\TestCase
{
    private ProductKitItemProductUnitDoctrineListener $listener;

    protected function setUp(): void
    {
        $this->listener = new ProductKitItemProductUnitDoctrineListener();
    }

    public function testPreUpdateWhenProductUnitIsNotChanged(): void
    {
        $productKitItem = $this->createMock(ProductKitItem::class);

        $productKitItem
            ->expects(self::never())
            ->method(self::anything());

        $changeSet = [];
        $eventArgs = new PreUpdateEventArgs(
            $productKitItem,
            $this->createMock(EntityManagerInterface::class),
            $changeSet
        );
        $this->listener->preUpdate($productKitItem, $eventArgs);
    }

    public function testPreUpdateWhenProductUnitIsChanged(): void
    {
        $productUnit = (new ProductUnit())->setCode('item');
        $product1UnitPrecision = (new ProductUnitPrecision())->setUnit($productUnit);
        $product1 = (new ProductStub())
            ->setId(424242)
            ->addUnitPrecision($product1UnitPrecision);

        $productKitItemProduct = (new ProductKitItemProduct())
            ->setProduct($product1);
        $productKitItem = (new ProductKitItemStub(42))
            ->setProductUnit($productUnit)
            ->addKitItemProduct($productKitItemProduct);
        $changeSet = ['productUnit' => [null, $productUnit]];
        $eventArgs = new PreUpdateEventArgs(
            $productKitItem,
            $this->createMock(EntityManagerInterface::class),
            $changeSet
        );

        self::assertNull($productKitItemProduct->getProductUnitPrecision());

        $this->listener->preUpdate($productKitItem, $eventArgs);

        self::assertSame($product1UnitPrecision, $productKitItemProduct->getProductUnitPrecision());
    }

    public function testPreUpdateWhenProductUnitIsChangedToNull(): void
    {
        $productUnit = (new ProductUnit())->setCode('item');
        $product1UnitPrecision = (new ProductUnitPrecision())->setUnit($productUnit);
        $product1 = (new ProductStub())
            ->setId(424242)
            ->addUnitPrecision($product1UnitPrecision);

        $productKitItemProduct = (new ProductKitItemProduct())
            ->setProduct($product1)
            ->setProductUnitPrecision($product1UnitPrecision);
        $productKitItem = (new ProductKitItemStub(42))
            ->addKitItemProduct($productKitItemProduct);
        $changeSet = ['productUnit' => [$productUnit, null]];
        $eventArgs = new PreUpdateEventArgs(
            $productKitItem,
            $this->createMock(EntityManagerInterface::class),
            $changeSet
        );

        self::assertSame($product1UnitPrecision, $productKitItemProduct->getProductUnitPrecision());

        $this->listener->preUpdate($productKitItem, $eventArgs);

        self::assertNull($productKitItemProduct->getProductUnitPrecision());
    }

    public function testPreUpdateWhenProductUnitIsChangedAndUnitPrecisionIsMissing(): void
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
        $productKitItem = (new ProductKitItemStub(42))
            ->setProductUnit($newProductUnit)
            ->addKitItemProduct($productKitItemProduct);
        $changeSet = ['productUnit' => [$productUnit, $newProductUnit]];
        $eventArgs = new PreUpdateEventArgs(
            $productKitItem,
            $this->createMock(EntityManagerInterface::class),
            $changeSet
        );

        self::assertSame($product1UnitPrecision, $productKitItemProduct->getProductUnitPrecision());

        $this->listener->preUpdate($productKitItem, $eventArgs);

        self::assertNull($productKitItemProduct->getProductUnitPrecision());
    }
}
