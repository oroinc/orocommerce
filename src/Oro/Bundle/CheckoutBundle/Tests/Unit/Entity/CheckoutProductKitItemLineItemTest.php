<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Entity;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutProductKitItemLineItem;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class CheckoutProductKitItemLineItemTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $properties = [
            ['id', 123],
            ['lineItem', new CheckoutLineItem()],
            ['kitItem', new ProductKitItem()],
            ['product', new Product()],
            ['quantity', 123.4567],
            ['productUnit', new ProductUnit()],
            ['sortOrder', 42],
            ['value', 12.3456],
            ['currency', 'USD'],
            ['price', Price::create(34.5678, 'USD')],
            ['priceFixed', true],
        ];

        self::assertPropertyAccessors(new CheckoutProductKitItemLineItem(), $properties);
    }

    public function testGetEntityIdentifier(): void
    {
        $kitItemLineItem = new CheckoutProductKitItemLineItem();
        self::assertNull($kitItemLineItem->getEntityIdentifier());

        $id = 42;
        ReflectionUtil::setId($kitItemLineItem, $id);

        self::assertEquals($id, $kitItemLineItem->getEntityIdentifier());
    }

    public function testGetProductSku(): void
    {
        $product = new Product();
        $kitItemLineItem = (new CheckoutProductKitItemLineItem())
            ->setProduct($product);
        self::assertNull($kitItemLineItem->getProductSku());

        $sku = 'sku123';
        $product->setSku($sku);

        self::assertEquals($sku, $kitItemLineItem->getProductSku());
    }

    public function testGetProductHolder(): void
    {
        $kitItemLineItem = new CheckoutProductKitItemLineItem();
        self::assertSame($kitItemLineItem, $kitItemLineItem->getProductHolder());
    }

    public function testGetProductUnit(): void
    {
        $kitItemLineItem = new CheckoutProductKitItemLineItem();
        self::assertNull($kitItemLineItem->getProductUnit());

        $productUnit = new ProductUnit();
        $kitItemLineItem->setProductUnit($productUnit);

        self::assertSame($productUnit, $kitItemLineItem->getProductUnit());
    }

    public function testGetProductUnitCode(): void
    {
        $kitItemLineItem = new CheckoutProductKitItemLineItem();
        self::assertNull($kitItemLineItem->getProductUnitCode());

        $unitCode = 'sample_code';
        $productUnit = (new ProductUnit())->setCode($unitCode);
        $kitItemLineItem->setProductUnit($productUnit);

        self::assertSame($unitCode, $kitItemLineItem->getProductUnitCode());
    }

    public function testGetParentProduct(): void
    {
        self::assertNull((new CheckoutProductKitItemLineItem())->getParentProduct());
    }

    public function testPrice(): void
    {
        $entity = new CheckoutProductKitItemLineItem();
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
