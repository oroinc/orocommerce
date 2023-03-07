<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener\Doctrine;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\EventListener\Doctrine\ProductKitUnitOfQuantityDoctrineListener;
use Oro\Bundle\ProductBundle\Exception\InvalidProductKitItemEmptyProductsException;
use Oro\Bundle\ProductBundle\Exception\ProductKitItemEmptyProductUnitException;
use Oro\Bundle\ProductBundle\Exception\ProductKitItemInvalidProductUnitException;
use Oro\Bundle\ProductBundle\Service\ProductKitItemProductUnitChecker;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;

class ProductKitUnitOfQuantityDoctrineListenerTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private ProductKitUnitOfQuantityDoctrineListener $listener;

    protected function setUp(): void
    {
        $productUnitChecker = new ProductKitItemProductUnitChecker();
        $this->listener = new ProductKitUnitOfQuantityDoctrineListener($productUnitChecker);

        $this->setUpLoggerMock($this->listener);
    }

    public function testPrePersistThrowsExceptionWhenNoProductKit(): void
    {
        $productKitItem = new ProductKitItemStub(42);

        $this->expectExceptionObject(new \LogicException('ProductKitItem::$productKit was not expected to be empty'));

        $this->listener->prePersist($productKitItem, $this->createMock(LifecycleEventArgs::class));
    }

    public function testPreUpdateThrowsExceptionWhenNoProductKit(): void
    {
        $productKitItem = new ProductKitItemStub(42);

        $this->expectExceptionObject(new \LogicException('ProductKitItem::$productKit was not expected to be empty'));

        $this->listener->preUpdate($productKitItem, $this->createMock(PreUpdateEventArgs::class));
    }

    public function testPrePersistThrowsExceptionWhenEmptyProducts(): void
    {
        $productKit = (new ProductStub())->setId(4242);
        $productKitItem = (new ProductKitItemStub(42))
            ->setProductKit($productKit);

        $this->expectExceptionObject(new InvalidProductKitItemEmptyProductsException($productKitItem));

        $this->loggerMock
            ->expects(self::once())
            ->method('debug')
            ->with(
                'It is not possible to create a ProductKitItem with empty kitItemProducts collection',
                ['product_kit_item' => $productKitItem]
            );

        $this->listener->prePersist($productKitItem, $this->createMock(LifecycleEventArgs::class));
    }

    public function testPreUpdateThrowsExceptionWhenEmptyProducts(): void
    {
        $productKit = (new ProductStub())->setId(4242);
        $productKitItem = (new ProductKitItemStub(42))
            ->setProductKit($productKit);

        $this->expectExceptionObject(new InvalidProductKitItemEmptyProductsException($productKitItem));

        $this->loggerMock
            ->expects(self::once())
            ->method('debug')
            ->with(
                'It is not possible to create a ProductKitItem with empty kitItemProducts collection',
                ['product_kit_item' => $productKitItem]
            );

        $this->listener->preUpdate($productKitItem, $this->createMock(PreUpdateEventArgs::class));
    }

    public function testPrePersistThrowsExceptionWhenEmptyProductUnit(): void
    {
        $productKit = (new ProductStub())->setId(4242);
        $product1 = (new ProductStub())->setId(424242);
        $productKitItem = (new ProductKitItemStub(42))
            ->setProductKit($productKit)
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($product1));

        $this->expectExceptionObject(new ProductKitItemEmptyProductUnitException($productKitItem));

        $this->loggerMock
            ->expects(self::once())
            ->method('debug')
            ->with(
                'It is not possible to create a ProductKitItem that has empty productUnit',
                ['product_kit_item' => $productKitItem]
            );

        $this->listener->prePersist($productKitItem, $this->createMock(LifecycleEventArgs::class));
    }

    public function testPreUpdateThrowsExceptionWhenEmptyProductUnit(): void
    {
        $productKit = (new ProductStub())->setId(4242);
        $product1 = (new ProductStub())->setId(424242);
        $productKitItem = (new ProductKitItemStub(42))
            ->setProductKit($productKit)
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($product1));

        $this->expectExceptionObject(new ProductKitItemEmptyProductUnitException($productKitItem));

        $this->loggerMock
            ->expects(self::once())
            ->method('debug')
            ->with(
                'It is not possible to create a ProductKitItem that has empty productUnit',
                ['product_kit_item' => $productKitItem]
            );

        $this->listener->preUpdate($productKitItem, $this->createMock(PreUpdateEventArgs::class));
    }

    public function testPrePersistThrowsExceptionWhenInvalidProductUnit(): void
    {
        $productKit = (new ProductStub())->setId(4242);
        $product1 = (new ProductStub())->setId(424242);
        $productUnit = (new ProductUnit())->setCode('item');
        $productKitItem = (new ProductKitItemStub(42))
            ->setProductKit($productKit)
            ->setProductUnit($productUnit)
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($product1));

        $this->expectExceptionObject(
            new ProductKitItemInvalidProductUnitException($productKitItem, $productUnit, $product1)
        );

        $this->loggerMock
            ->expects(self::once())
            ->method('debug')
            ->with(
                'Product unit "{product_unit}" cannot be used in ProductKitItem'
                . ' because it is not present in each product unit precisions collection of the ProductKitItem'
                . ' $products collection',
                [
                    'product_kit_item' => $productKitItem,
                    'product_unit' => $productUnit->getCode(),
                ]
            );

        $this->listener->prePersist($productKitItem, $this->createMock(LifecycleEventArgs::class));
    }

    public function testPreUpdateThrowsExceptionWhenInvalidProductUnit(): void
    {
        $productKit = (new ProductStub())->setId(4242);
        $product1 = (new ProductStub())->setId(424242);
        $productUnit = (new ProductUnit())->setCode('item');
        $productKitItem = (new ProductKitItemStub(42))
            ->setProductKit($productKit)
            ->setProductUnit($productUnit)
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($product1));

        $this->expectExceptionObject(
            new ProductKitItemInvalidProductUnitException($productKitItem, $productUnit)
        );

        $this->loggerMock
            ->expects(self::once())
            ->method('debug')
            ->with(
                'Product unit "{product_unit}" cannot be used in ProductKitItem'
                . ' because it is not present in each product unit precisions collection of the ProductKitItem'
                . ' $products collection',
                [
                    'product_kit_item' => $productKitItem,
                    'product_unit' => $productUnit->getCode(),
                ]
            );

        $this->listener->preUpdate($productKitItem, $this->createMock(PreUpdateEventArgs::class));
    }

    public function testPrePersistWhenProductKitItemWithProductUnit(): void
    {
        $productUnit = (new ProductUnit())->setCode('item');
        $productKitUnitPrecision = (new ProductUnitPrecision())->setUnit($productUnit);
        $productKit = (new ProductStub())
            ->setId(4242)
            ->setPrimaryUnitPrecision($productKitUnitPrecision);

        $product1UnitPrecision = (new ProductUnitPrecision())->setUnit($productUnit);
        $product1 = (new ProductStub())
            ->setId(424242)
            ->addUnitPrecision($product1UnitPrecision);

        $productKitItem = (new ProductKitItemStub(42))
            ->setProductKit($productKit)
            ->setProductUnit($productUnit)
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($product1));

        self::assertSame($productUnit, $productKitItem->getProductUnit());
        self::assertCount(0, $productKitItem->getReferencedUnitPrecisions());

        $this->listener->prePersist($productKitItem, $this->createMock(LifecycleEventArgs::class));

        self::assertSame($productUnit, $productKitItem->getProductUnit());
        self::assertCount(1, $productKitItem->getReferencedUnitPrecisions());
    }

    public function testPreUpdateWhenProductKitItemWithProductUnit(): void
    {
        $productUnit = (new ProductUnit())->setCode('item');
        $productKitUnitPrecision = (new ProductUnitPrecision())->setUnit($productUnit);
        $productKit = (new ProductStub())
            ->setId(4242)
            ->setPrimaryUnitPrecision($productKitUnitPrecision);

        $product1UnitPrecision = (new ProductUnitPrecision())->setUnit($productUnit);
        $product1 = (new ProductStub())
            ->setId(424242)
            ->addUnitPrecision($product1UnitPrecision);

        $productKitItem = (new ProductKitItemStub(42))
            ->setProductKit($productKit)
            ->setProductUnit($productUnit)
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($product1));

        self::assertSame($productUnit, $productKitItem->getProductUnit());
        self::assertCount(0, $productKitItem->getReferencedUnitPrecisions());

        $this->listener->preUpdate($productKitItem, $this->createMock(PreUpdateEventArgs::class));

        self::assertSame($productUnit, $productKitItem->getProductUnit());
        self::assertCount(1, $productKitItem->getReferencedUnitPrecisions());
    }
}
