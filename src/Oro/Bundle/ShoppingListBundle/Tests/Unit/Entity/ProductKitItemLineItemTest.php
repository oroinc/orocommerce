<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class ProductKitItemLineItemTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $properties = [
            ['id', 123],
            ['lineItem', new LineItem()],
            ['kitItem', new ProductKitItem()],
            ['product', new Product()],
            ['quantity', 12.5],
            ['unit', new ProductUnit()],
            ['sortOrder', 42],
        ];

        self::assertPropertyAccessors(new ProductKitItemLineItem(), $properties);
    }

    public function testGetEntityIdentifier(): void
    {
        $kitItemLineItem = new ProductKitItemLineItem();
        self::assertNull($kitItemLineItem->getEntityIdentifier());

        $id = 42;
        ReflectionUtil::setId($kitItemLineItem, $id);

        self::assertEquals($id, $kitItemLineItem->getEntityIdentifier());
    }

    public function testGetProductSku(): void
    {
        $product = new Product();
        $kitItemLineItem = (new ProductKitItemLineItem())
            ->setProduct($product);
        self::assertNull($kitItemLineItem->getProductSku());

        $sku = 'sku123';
        $product->setSku($sku);

        self::assertEquals($sku, $kitItemLineItem->getProductSku());
    }

    public function testGetProductHolder(): void
    {
        $kitItemLineItem = new ProductKitItemLineItem();
        self::assertSame($kitItemLineItem, $kitItemLineItem->getProductHolder());
    }

    public function testGetProductUnit(): void
    {
        $kitItemLineItem = new ProductKitItemLineItem();
        self::assertNull($kitItemLineItem->getProductUnit());

        $productUnit = new ProductUnit();
        $kitItemLineItem->setUnit($productUnit);

        self::assertSame($productUnit, $kitItemLineItem->getProductUnit());
    }

    public function testGetProductUnitCode(): void
    {
        $kitItemLineItem = new ProductKitItemLineItem();
        self::assertNull($kitItemLineItem->getProductUnitCode());

        $unitCode = 'sample_code';
        $productUnit = (new ProductUnit())->setCode($unitCode);
        $kitItemLineItem->setUnit($productUnit);

        self::assertSame($unitCode, $kitItemLineItem->getProductUnitCode());
    }

    public function testGetParentProduct(): void
    {
        self::assertNull((new ProductKitItemLineItem())->getParentProduct());
    }
}
