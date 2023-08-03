<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Entity;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OrderProductKitItemLineItemTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $properties = [
            ['id', 123],
            ['lineItem', new OrderLineItem()],
            ['kitItem', new ProductKitItemStub()],
            ['kitItemLabel', 'sample label'],
            ['kitItemOptional', true],
            ['product', new Product()],
            ['productSku', 'sku123'],
            ['productName', 'sample name'],
            ['quantity', 123.4567],
            ['unit', new ProductUnit()],
            ['unitCode', 'sample_code'],
            ['sortOrder', 42],
            ['value', 12.3456],
            ['currency', 'USD'],
            ['price', Price::create(34.5678, 'USD')],
        ];

        self::assertPropertyAccessors(new OrderProductKitItemLineItem(), $properties);
    }

    public function testGetEntityIdentifier(): void
    {
        $kitItemLineItem = new OrderProductKitItemLineItem();
        self::assertNull($kitItemLineItem->getEntityIdentifier());

        $id = 42;
        ReflectionUtil::setId($kitItemLineItem, $id);

        self::assertEquals($id, $kitItemLineItem->getEntityIdentifier());
    }

    public function testGetProductSku(): void
    {
        $product = new Product();
        $kitItemLineItem = (new OrderProductKitItemLineItem())->setProduct($product);
        self::assertNull($kitItemLineItem->getProductSku());

        $product2 = (new Product())->setSku('sku456');
        $kitItemLineItem->setProduct($product2);
        self::assertEquals($product2->getSku(), $kitItemLineItem->getProductSku());

        $kitItemLineItem->setProduct(null);
        self::assertEquals($product2->getSku(), $kitItemLineItem->getProductSku());
    }

    public function testGetProductName(): void
    {
        $product = new ProductStub();
        $kitItemLineItem = (new OrderProductKitItemLineItem())->setProduct($product);
        self::assertNull($kitItemLineItem->getProductName());

        $product2 = (new ProductStub())->setDefaultName('sample-name');
        $kitItemLineItem->setProduct($product2);
        self::assertEquals($product2->getDefaultName(), $kitItemLineItem->getProductName());

        $kitItemLineItem->setProduct(null);
        self::assertEquals($product2->getDefaultName(), $kitItemLineItem->getProductName());
    }

    public function testGetKitItemLabel(): void
    {
        $kitItem = new ProductKitItemStub();
        $kitItemLineItem = (new OrderProductKitItemLineItem())->setKitItem($kitItem);
        self::assertNull($kitItemLineItem->getProductName());

        $kitItem2 = (new ProductKitItemStub())->setDefaultLabel('sample-name');
        $kitItemLineItem->setKitItem($kitItem2);
        self::assertEquals($kitItem2->getDefaultLabel(), $kitItemLineItem->getKitItemLabel());

        $kitItemLineItem->setKitItem(null);
        self::assertEquals($kitItem2->getDefaultLabel(), $kitItemLineItem->getKitItemLabel());
    }

    public function testGetKitItemOptional(): void
    {
        $kitItem = new ProductKitItemStub();
        $kitItemLineItem = (new OrderProductKitItemLineItem())->setKitItem($kitItem);
        self::assertFalse($kitItemLineItem->isKitItemOptional());

        $kitItem2 = (new ProductKitItemStub())->setOptional(true);
        $kitItemLineItem->setKitItem($kitItem2);
        self::assertTrue($kitItemLineItem->isKitItemOptional());

        $kitItemLineItem->setKitItem(null);
        self::assertTrue($kitItemLineItem->isKitItemOptional());
    }

    public function testGetProductHolder(): void
    {
        $kitItemLineItem = new OrderProductKitItemLineItem();
        self::assertSame($kitItemLineItem, $kitItemLineItem->getProductHolder());
    }

    public function testGetProductUnit(): void
    {
        $kitItemLineItem = new OrderProductKitItemLineItem();
        self::assertNull($kitItemLineItem->getProductUnit());

        $productUnit = new ProductUnit();
        $kitItemLineItem->setUnit($productUnit);

        self::assertSame($productUnit, $kitItemLineItem->getProductUnit());
    }

    public function testGetProductUnitCode(): void
    {
        $kitItemLineItem = new OrderProductKitItemLineItem();
        self::assertNull($kitItemLineItem->getProductUnitCode());

        $productUnit = (new ProductUnit())->setCode('sample_code');
        $kitItemLineItem->setUnit($productUnit);

        self::assertEquals($productUnit->getCode(), $kitItemLineItem->getProductUnitCode());

        $kitItemLineItem->setUnit(null);
        self::assertEquals($productUnit->getCode(), $kitItemLineItem->getProductUnitCode());
    }

    public function testUpdateFallbackFields(): void
    {
        $product = new ProductStub();
        $productUnit = new ProductUnit();
        $kitItem = new ProductKitItemStub();
        $kitItemLineItem = (new OrderProductKitItemLineItem())
            ->setProduct($product)
            ->setUnit($productUnit)
            ->setKitItem($kitItem);

        $kitItemLineItem->updateFallbackFields();

        self::assertNull($kitItemLineItem->getProductSku());
        self::assertNull($kitItemLineItem->getProductName());
        self::assertNull($kitItemLineItem->getProductUnitCode());
        self::assertNull($kitItemLineItem->getKitItemLabel());
        self::assertFalse($kitItemLineItem->isKitItemOptional());

        $product
            ->setSku('sku123')
            ->setDefaultName('sample name');

        $productUnit->setCode('sample_code');

        $kitItem
            ->setDefaultLabel('sample label')
            ->setOptional(true);

        $kitItemLineItem->updateFallbackFields();

        self::assertEquals($product->getSku(), $kitItemLineItem->getProductSku());
        self::assertEquals($product->getDefaultName(), $kitItemLineItem->getProductName());
        self::assertEquals($productUnit->getCode(), $kitItemLineItem->getProductUnitCode());
        self::assertEquals($kitItem->getDefaultLabel(), $kitItemLineItem->getKitItemLabel());
        self::assertTrue($kitItemLineItem->isKitItemOptional());
    }

    public function testGetParentProduct(): void
    {
        self::assertNull((new OrderProductKitItemLineItem())->getParentProduct());
    }

    public function testPrice(): void
    {
        $entity = new OrderProductKitItemLineItem();
        self::assertNull($entity->getPrice());
        self::assertNull($entity->getCurrency());
        self::assertNull($entity->getValue());

        $price = Price::create(12.3456, 'USD');
        $entity->setPrice($price);
        self::assertSame($price->getCurrency(), $entity->getCurrency());
        self::assertSame((float)$price->getValue(), $entity->getValue());

        $entity->setValue(34.5678);
        self::assertEquals(Price::create(34.5678, 'USD'), $entity->getPrice());

        $entity->setCurrency('EUR');
        self::assertEquals(Price::create(34.5678, 'EUR'), $entity->getPrice());
    }
}
