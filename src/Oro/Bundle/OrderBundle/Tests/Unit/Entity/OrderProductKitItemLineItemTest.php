<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Entity;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
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
            ['kitItemId', 142],
            ['kitItemLabel', 'sample label'],
            ['optional', true],
            ['minimumQuantity', 12.3456],
            ['maximumQuantity', 34.5678],
            ['product', new Product()],
            ['productId', 42],
            ['productSku', 'sku123'],
            ['productName', 'sample name'],
            ['quantity', 123.4567],
            ['productUnit', new ProductUnit()],
            ['productUnitCode', 'sample_code'],
            ['productUnitPrecision', 3],
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

    public function testGetProductId(): void
    {
        $product = new Product();
        $kitItemLineItem = (new OrderProductKitItemLineItem())->setProduct($product);
        self::assertNull($kitItemLineItem->getProductId());

        $product2 = (new ProductStub())->setId(42);
        $kitItemLineItem->setProduct($product2);
        self::assertEquals($product2->getId(), $kitItemLineItem->getProductId());

        $kitItemLineItem->setProduct(null);
        self::assertNull($kitItemLineItem->getProductId());
    }

    public function testGetProductSku(): void
    {
        $product = new Product();
        $kitItemLineItem = (new OrderProductKitItemLineItem())->setProduct($product);
        self::assertNull($kitItemLineItem->getProductSku());

        $product2 = (new ProductStub())->setId(42)->setSku('sku456');
        $kitItemLineItem->setProduct($product2);
        self::assertEquals($product2->getSku(), $kitItemLineItem->getProductSku());

        $kitItemLineItem->setProduct(null);
        self::assertNull($kitItemLineItem->getProductSku());
    }

    public function testGetProductName(): void
    {
        $product = new ProductStub();
        $kitItemLineItem = (new OrderProductKitItemLineItem())->setProduct($product);
        self::assertNull($kitItemLineItem->getProductName());

        $product2 = (new ProductStub())->setId(42)->setDefaultName('sample-name');
        $kitItemLineItem->setProduct($product2);
        self::assertEquals($product2->getDefaultName(), $kitItemLineItem->getProductName());

        $kitItemLineItem->setProduct(null);
        self::assertNull($kitItemLineItem->getProductName());
    }

    public function testGetKitItemId(): void
    {
        $kitItem = new ProductKitItemStub();
        $kitItemLineItem = (new OrderProductKitItemLineItem())->setKitItem($kitItem);
        self::assertNull($kitItemLineItem->getKitItemId());

        $kitItem2 = (new ProductKitItemStub())->setId(142);
        $kitItemLineItem->setKitItem($kitItem2);
        self::assertEquals($kitItem2->getId(), $kitItemLineItem->getKitItemId());

        $kitItemLineItem->setKitItem(null);
        self::assertNull($kitItemLineItem->getKitItemId());
    }

    public function testGetKitItemLabel(): void
    {
        $kitItem = new ProductKitItemStub();
        $kitItemLineItem = (new OrderProductKitItemLineItem())->setKitItem($kitItem);
        self::assertNull($kitItemLineItem->getKitItemLabel());

        $kitItem2 = (new ProductKitItemStub())->setId(142)->setDefaultLabel('sample-name');
        $kitItemLineItem->setKitItem($kitItem2);
        self::assertEquals($kitItem2->getDefaultLabel(), $kitItemLineItem->getKitItemLabel());

        $kitItemLineItem->setKitItem(null);
        self::assertNull($kitItemLineItem->getKitItemLabel());
    }

    public function testGetOptional(): void
    {
        $kitItem = new ProductKitItemStub();
        $kitItemLineItem = (new OrderProductKitItemLineItem())->setKitItem($kitItem);
        self::assertFalse($kitItemLineItem->isOptional());

        $kitItem2 = (new ProductKitItemStub())->setId(142)->setOptional(true);
        $kitItemLineItem->setKitItem($kitItem2);
        self::assertTrue($kitItemLineItem->isOptional());

        $kitItemLineItem->setKitItem(null);
        self::assertFalse($kitItemLineItem->isOptional());
    }

    public function testGetMinimumQuantity(): void
    {
        $kitItem = new ProductKitItemStub();
        $kitItemLineItem = (new OrderProductKitItemLineItem())->setKitItem($kitItem);
        self::assertNull($kitItemLineItem->getMinimumQuantity());

        $kitItem2 = (new ProductKitItemStub())->setId(142)->setMinimumQuantity(12.3456);
        $kitItemLineItem->setKitItem($kitItem2);
        self::assertEquals($kitItem2->getMinimumQuantity(), $kitItemLineItem->getMinimumQuantity());

        $kitItemLineItem->setKitItem(null);
        self::assertNull($kitItemLineItem->getMinimumQuantity());
    }

    public function testGetMaximumQuantity(): void
    {
        $kitItem = new ProductKitItemStub();
        $kitItemLineItem = (new OrderProductKitItemLineItem())->setKitItem($kitItem);
        self::assertNull($kitItemLineItem->getMaximumQuantity());

        $kitItem2 = (new ProductKitItemStub())->setId(142)->setMinimumQuantity(34.5678);
        $kitItemLineItem->setKitItem($kitItem2);
        self::assertEquals($kitItem2->getMaximumQuantity(), $kitItemLineItem->getMaximumQuantity());

        $kitItemLineItem->setKitItem(null);
        self::assertNull($kitItemLineItem->getMaximumQuantity());
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
        $kitItemLineItem->setProductUnit($productUnit);

        self::assertSame($productUnit, $kitItemLineItem->getProductUnit());
    }

    public function testGetProductUnitCode(): void
    {
        $kitItemLineItem = new OrderProductKitItemLineItem();
        self::assertNull($kitItemLineItem->getProductUnitCode());

        $productUnit = (new ProductUnit())->setCode('sample_code');
        $kitItemLineItem->setProductUnit($productUnit);

        self::assertEquals($productUnit->getCode(), $kitItemLineItem->getProductUnitCode());

        $kitItemLineItem->setProductUnit(null);
        self::assertNull($kitItemLineItem->getProductUnitCode());
    }

    public function testUpdateFallbackFieldsWhenNoData(): void
    {
        $kitItemLineItem = new OrderProductKitItemLineItem();

        $kitItemLineItem->updateFallbackFields();

        self::assertNull($kitItemLineItem->getProductId());
        self::assertNull($kitItemLineItem->getProductSku());
        self::assertNull($kitItemLineItem->getProductName());
        self::assertNull($kitItemLineItem->getProductUnitCode());
        self::assertNull($kitItemLineItem->getKitItemId());
        self::assertNull($kitItemLineItem->getKitItemLabel());
        self::assertNull($kitItemLineItem->getMinimumQuantity());
        self::assertNull($kitItemLineItem->getMaximumQuantity());
        self::assertEquals(0, $kitItemLineItem->getProductUnitPrecision());
        self::assertFalse($kitItemLineItem->isOptional());
    }

    public function testUpdateFallbackFieldsWhenIsNew(): void
    {
        $productUnit = (new ProductUnit())
            ->setCode('sample_code');
        $unitPrecision = (new ProductUnitPrecision())->setUnit($productUnit)->setPrecision(3);
        $product = (new ProductStub())
            ->setId(42)
            ->setSku('sku123')
            ->setDefaultName('sample name')
            ->setPrimaryUnitPrecision($unitPrecision);
        $kitItem = (new ProductKitItemStub())
            ->setId(142)
            ->setDefaultLabel('sample label')
            ->setOptional(true)
            ->setMinimumQuantity(12.3456)
            ->setMaximumQuantity(34.5678);
        $kitItemLineItem = (new OrderProductKitItemLineItem())
            ->setProduct($product)
            ->setProductUnit($productUnit)
            ->setKitItem($kitItem);

        $kitItemLineItem->updateFallbackFields();

        self::assertEquals($product->getId(), $kitItemLineItem->getProductId());
        self::assertEquals($product->getSku(), $kitItemLineItem->getProductSku());
        self::assertEquals($product->getDefaultName(), $kitItemLineItem->getProductName());
        self::assertEquals($productUnit->getCode(), $kitItemLineItem->getProductUnitCode());
        self::assertEquals($unitPrecision->getPrecision(), $kitItemLineItem->getProductUnitPrecision());
        self::assertEquals($kitItem->getId(), $kitItemLineItem->getKitItemId());
        self::assertEquals($kitItem->getDefaultLabel(), $kitItemLineItem->getKitItemLabel());
        self::assertEquals($kitItem->getMinimumQuantity(), $kitItemLineItem->getMinimumQuantity());
        self::assertEquals($kitItem->getMaximumQuantity(), $kitItemLineItem->getMaximumQuantity());
        self::assertTrue($kitItemLineItem->isOptional());
    }

    public function testUpdateFallbackFieldsWhenBecomeExplicitlyNull(): void
    {
        $productUnit = (new ProductUnit())
            ->setCode('sample_code');
        $unitPrecision = (new ProductUnitPrecision())->setUnit($productUnit)->setPrecision(3);
        $product = (new ProductStub())
            ->setId(42)
            ->setSku('sku123')
            ->setDefaultName('sample name')
            ->setPrimaryUnitPrecision($unitPrecision);
        $kitItem = (new ProductKitItemStub())
            ->setId(142)
            ->setDefaultLabel('sample label')
            ->setOptional(true)
            ->setMinimumQuantity(12.3456)
            ->setMaximumQuantity(34.5678);
        $kitItemLineItem = (new OrderProductKitItemLineItem())
            ->setProduct($product)
            ->setProductUnit($productUnit)
            ->setKitItem($kitItem);

        $kitItemLineItem->updateFallbackFields();

        $kitItemLineItem
            ->setProduct(null)
            ->setProductUnit(null)
            ->setKitItem(null);

        $kitItemLineItem->updateFallbackFields();

        self::assertNull($kitItemLineItem->getProductId());
        self::assertNull($kitItemLineItem->getProductSku());
        self::assertNull($kitItemLineItem->getProductName());
        self::assertNull($kitItemLineItem->getProductUnitCode());
        self::assertEquals(0, $kitItemLineItem->getProductUnitPrecision());
        self::assertNull($kitItemLineItem->getKitItemId());
        self::assertNull($kitItemLineItem->getKitItemLabel());
        self::assertNull($kitItemLineItem->getMinimumQuantity());
        self::assertNull($kitItemLineItem->getMaximumQuantity());
        self::assertFalse($kitItemLineItem->isOptional());
    }

    public function testUpdateFallbackFieldsWhenImplicitlyNull(): void
    {
        $productUnit = (new ProductUnit())
            ->setCode('sample_code');
        $unitPrecision = (new ProductUnitPrecision())->setUnit($productUnit)->setPrecision(3);
        $product = (new ProductStub())
            ->setId(42)
            ->setSku('sku123')
            ->setDefaultName('sample name')
            ->setPrimaryUnitPrecision($unitPrecision);
        $kitItem = (new ProductKitItemStub())
            ->setId(142)
            ->setDefaultLabel('sample label')
            ->setOptional(true)
            ->setMinimumQuantity(12.3456)
            ->setMaximumQuantity(34.5678);
        $kitItemLineItem = (new OrderProductKitItemLineItem())
            ->setProduct($product)
            ->setProductUnit($productUnit)
            ->setKitItem($kitItem);

        $kitItemLineItem->updateFallbackFields();

        ReflectionUtil::setPropertyValue($kitItemLineItem, 'product', null);
        ReflectionUtil::setPropertyValue($kitItemLineItem, 'productUnit', null);
        ReflectionUtil::setPropertyValue($kitItemLineItem, 'kitItem', null);

        $kitItemLineItem->updateFallbackFields();

        self::assertEquals($product->getId(), $kitItemLineItem->getProductId());
        self::assertEquals($product->getSku(), $kitItemLineItem->getProductSku());
        self::assertEquals($product->getDefaultName(), $kitItemLineItem->getProductName());
        self::assertEquals($productUnit->getCode(), $kitItemLineItem->getProductUnitCode());
        self::assertEquals($unitPrecision->getPrecision(), $kitItemLineItem->getProductUnitPrecision());
        self::assertEquals($kitItem->getId(), $kitItemLineItem->getKitItemId());
        self::assertEquals($kitItem->getDefaultLabel(), $kitItemLineItem->getKitItemLabel());
        self::assertEquals($kitItem->getMinimumQuantity(), $kitItemLineItem->getMinimumQuantity());
        self::assertEquals($kitItem->getMaximumQuantity(), $kitItemLineItem->getMaximumQuantity());
        self::assertTrue($kitItemLineItem->isOptional());
    }

    public function testUpdateFallbackFieldsWhenChanged(): void
    {
        $productUnit = (new ProductUnit())
            ->setCode('sample_code');
        $unitPrecision = (new ProductUnitPrecision())->setUnit($productUnit)->setPrecision(3);
        $product = (new ProductStub())
            ->setId(42)
            ->setSku('sku123')
            ->setDefaultName('sample name')
            ->setPrimaryUnitPrecision($unitPrecision);
        $kitItem = (new ProductKitItemStub())
            ->setId(142)
            ->setDefaultLabel('sample label')
            ->setOptional(true)
            ->setMinimumQuantity(12.3456)
            ->setMaximumQuantity(34.5678);
        $kitItemLineItem = (new OrderProductKitItemLineItem())
            ->setProduct($product)
            ->setProductUnit($productUnit)
            ->setKitItem($kitItem);

        $kitItemLineItem->updateFallbackFields();

        $productUnit2 = (new ProductUnit())
            ->setCode('sample_code2');
        $unitPrecision2 = (new ProductUnitPrecision())->setUnit($productUnit2)->setPrecision(5);
        $product2 = (new ProductStub())
            ->setId(43)
            ->setSku('sku43')
            ->setDefaultName('sample new name')
            ->setPrimaryUnitPrecision($unitPrecision2);
        $kitItem2 = (new ProductKitItemStub())
            ->setId(143)
            ->setDefaultLabel('sample label2')
            ->setOptional(false)
            ->setMinimumQuantity(34.5678)
            ->setMaximumQuantity(56.7890);

        $kitItemLineItem
            ->setProduct($product2)
            ->setProductUnit($productUnit2)
            ->setKitItem($kitItem2);

        $kitItemLineItem->updateFallbackFields();

        self::assertEquals($product2->getId(), $kitItemLineItem->getProductId());
        self::assertEquals($product2->getSku(), $kitItemLineItem->getProductSku());
        self::assertEquals($product2->getDefaultName(), $kitItemLineItem->getProductName());
        self::assertEquals($productUnit2->getCode(), $kitItemLineItem->getProductUnitCode());
        self::assertEquals($unitPrecision2->getPrecision(), $kitItemLineItem->getProductUnitPrecision());
        self::assertEquals($kitItem2->getId(), $kitItemLineItem->getKitItemId());
        self::assertEquals($kitItem2->getDefaultLabel(), $kitItemLineItem->getKitItemLabel());
        self::assertEquals($kitItem2->getMinimumQuantity(), $kitItemLineItem->getMinimumQuantity());
        self::assertEquals($kitItem2->getMaximumQuantity(), $kitItemLineItem->getMaximumQuantity());
        self::assertFalse($kitItemLineItem->isOptional());
    }

    public function testUpdateUnitPrecisionWhenNoProductUnitPrecision(): void
    {
        $productUnit = (new ProductUnit())
            ->setCode('sample_code');
        $unitPrecision = (new ProductUnitPrecision())->setUnit($productUnit)->setPrecision(3);
        $product = (new ProductStub())
            ->setId(42)
            ->setSku('sku123')
            ->setDefaultName('sample name')
            ->setPrimaryUnitPrecision($unitPrecision);

        $kitItemLineItem = (new OrderProductKitItemLineItem())
            ->setProduct($product)
            ->setProductUnit($productUnit);

        $kitItemLineItem->updateFallbackFields();
        self::assertEquals($unitPrecision->getPrecision(), $kitItemLineItem->getProductUnitPrecision());

        $productUnit2 = (new ProductUnit())
            ->setCode('sample_code2')
            ->setDefaultPrecision(1);
        $product2 = (new ProductStub())
            ->setId(43)
            ->setSku('sku43')
            ->setDefaultName('sample new name');

        $kitItemLineItem
            ->setProduct($product2)
            ->setProductUnit($productUnit2);

        $kitItemLineItem->updateFallbackFields();

        self::assertEquals($productUnit2->getDefaultPrecision(), $kitItemLineItem->getProductUnitPrecision());
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

    public function testPriceWhenInvalid(): void
    {
        $entity = new OrderProductKitItemLineItem();

        $price = Price::create('foobar', 'USD');
        $entity->setPrice($price);
        self::assertSame($price->getCurrency(), $entity->getCurrency());
        self::assertSame(0.0, $entity->getValue());
        self::assertSame($price, $entity->getPrice());
    }

    public function testGetOrder(): void
    {
        $order = new Order();
        $entity = new OrderProductKitItemLineItem();
        $entity->setLineItem((new OrderLineItem())->setOrder($order));

        self::assertEquals($order, $entity->getOrder());
    }
}
