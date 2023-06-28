<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Entity;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class OrderProductKitItemLineItemTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $properties = [
            ['id', 123],
            ['lineItem', new OrderLineItem()],
            ['kitItem', new ProductKitItem()],
            ['product', new Product()],
            ['quantity', 123.4567],
            ['unit', new ProductUnit()],
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
        $kitItemLineItem = (new OrderProductKitItemLineItem())
            ->setProduct($product);
        self::assertNull($kitItemLineItem->getProductSku());

        $sku = 'sku123';
        $product->setSku($sku);

        self::assertEquals($sku, $kitItemLineItem->getProductSku());
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

        $unitCode = 'sample_code';
        $productUnit = (new ProductUnit())->setCode($unitCode);
        $kitItemLineItem->setUnit($productUnit);

        self::assertSame($unitCode, $kitItemLineItem->getProductUnitCode());
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
