<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener\Doctrine;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\EventListener\Doctrine\ProductKitUnitOfQuantityDoctrineListener;
use Oro\Bundle\ProductBundle\Exception\InvalidProductKitItemEmptyProductsException;
use Oro\Bundle\ProductBundle\Exception\InvalidProductKitItemUnitOfQuantityException;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;

class ProductKitUnitOfQuantityDoctrineListenerTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private ProductKitUnitOfQuantityDoctrineListener $listener;

    protected function setUp(): void
    {
        $this->listener = new ProductKitUnitOfQuantityDoctrineListener();

        $this->setUpLoggerMock($this->listener);
    }

    public function testPrePersistThrowsExceptionWhenNoProductKit(): void
    {
        $productKitItem = new ProductKitItemStub(42);

        $this->expectExceptionObject(
            new \LogicException(sprintf('%s::$productKit was not expected to be empty', ProductKitItem::class))
        );

        $this->listener->prePersist($productKitItem, $this->createMock(LifecycleEventArgs::class));
    }

    public function testPreUpdateThrowsExceptionWhenNoProductKit(): void
    {
        $productKitItem = new ProductKitItemStub(42);

        $this->expectExceptionObject(
            new \LogicException(sprintf('%s::$productKit was not expected to be empty', ProductKitItem::class))
        );

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
                'It is not possible to create a ProductKitItem (id: {product_kit_item_id}) '
                . 'that has empty "Products" collection',
                [
                    'product_kit_item_id' => $productKitItem->getId(),
                    'product_kit_item' => $productKitItem,
                ]
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
                'It is not possible to create a ProductKitItem (id: {product_kit_item_id}) '
                . 'that has empty "Products" collection',
                [
                    'product_kit_item_id' => $productKitItem->getId(),
                    'product_kit_item' => $productKitItem,
                ]
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
            ->addProduct($product1);

        $this->expectExceptionObject(
            new InvalidProductKitItemUnitOfQuantityException($productKitItem, $productUnit, $product1)
        );

        $this->loggerMock
            ->expects(self::once())
            ->method('debug')
            ->with(
                'ProductUnit "{product_unit}" cannot be used in ProductKitItem (id: {product_kit_item_id}) '
                . 'because it is not present in the unit precisions collection of product (id: {product_id})',
                [
                    'product_kit_item_id' => $productKitItem->getId(),
                    'product_unit' => $productUnit->getCode(),
                    'product_kit_item' => $productKitItem,
                    'product_id' => $product1->getId(),
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
            ->addProduct($product1);

        $this->expectExceptionObject(
            new InvalidProductKitItemUnitOfQuantityException($productKitItem, $productUnit, $product1)
        );

        $this->loggerMock
            ->expects(self::once())
            ->method('debug')
            ->with(
                'ProductUnit "{product_unit}" cannot be used in ProductKitItem (id: {product_kit_item_id}) '
                . 'because it is not present in the unit precisions collection of product (id: {product_id})',
                [
                    'product_kit_item_id' => $productKitItem->getId(),
                    'product_unit' => $productUnit->getCode(),
                    'product_kit_item' => $productKitItem,
                    'product_id' => $product1->getId(),
                ]
            );

        $this->listener->preUpdate($productKitItem, $this->createMock(PreUpdateEventArgs::class));
    }

    public function testPrePersistWhenProductKitItemWithoutProductUnit(): void
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
            ->addProduct($product1);

        self::assertNull($productKitItem->getProductUnit());
        self::assertCount(0, $productKitItem->getReferencedUnitPrecisions());

        $this->loggerMock
            ->expects(self::once())
            ->method('debug')
            ->with(
                '$productUnit is not specified for ProductKitItem (id: {product_kit_item_id}), '
                . 'trying to use ProductUnit "{product_unit}" from the product kit primary unit precision',
                [
                    'product_kit_item_id' => $productKitItem->getId(),
                    'product_unit' => $productUnit->getCode(),
                    'product_kit_item' => $productKitItem,
                ]
            );

        $this->listener->prePersist($productKitItem, $this->createMock(LifecycleEventArgs::class));

        self::assertEquals($productUnit, $productKitItem->getProductUnit());
        self::assertCount(1, $productKitItem->getReferencedUnitPrecisions());
    }

    public function testPreUpdateWhenProductKitItemWithoutProductUnit(): void
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
            ->addProduct($product1);

        self::assertNull($productKitItem->getProductUnit());
        self::assertCount(0, $productKitItem->getReferencedUnitPrecisions());

        $this->loggerMock
            ->expects(self::once())
            ->method('debug')
            ->with(
                '$productUnit is not specified for ProductKitItem (id: {product_kit_item_id}), '
                . 'trying to use ProductUnit "{product_unit}" from the product kit primary unit precision',
                [
                    'product_kit_item_id' => $productKitItem->getId(),
                    'product_unit' => $productUnit->getCode(),
                    'product_kit_item' => $productKitItem,
                ]
            );

        $this->listener->preUpdate($productKitItem, $this->createMock(PreUpdateEventArgs::class));

        self::assertSame($productUnit, $productKitItem->getProductUnit());
        self::assertCount(1, $productKitItem->getReferencedUnitPrecisions());
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
            ->addProduct($product1);

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
            ->addProduct($product1);

        self::assertSame($productUnit, $productKitItem->getProductUnit());
        self::assertCount(0, $productKitItem->getReferencedUnitPrecisions());

        $this->listener->preUpdate($productKitItem, $this->createMock(PreUpdateEventArgs::class));

        self::assertSame($productUnit, $productKitItem->getProductUnit());
        self::assertCount(1, $productKitItem->getReferencedUnitPrecisions());
    }
}
