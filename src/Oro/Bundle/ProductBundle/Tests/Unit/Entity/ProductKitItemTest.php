<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Entity;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemLabel;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ProductKitItemTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $now = new \DateTime('now');

        $properties = [
            ['id', 123],
            ['productUnit', new ProductUnit()],
            ['productKit', new Product()],
            ['sortOrder', 42],
            ['minimumQuantity', 42.42],
            ['maximumQuantity', 4242.42],
            ['optional', false, true],
            ['createdAt', $now, false],
            ['updatedAt', $now, false],
        ];

        self::assertPropertyAccessors(new ProductKitItem(), $properties);
    }

    public function testCollections(): void
    {
        $collections = [
            ['labels', new ProductKitItemLabel()],
            ['kitItemProducts', new ProductKitItemProduct()],
        ];

        self::assertPropertyCollections(new ProductKitItemStub(), $collections);
    }

    public function testGetProducts(): void
    {
        $kitItem = new ProductKitItemStub();
        self::assertEmpty($kitItem->getProducts());

        $productKitItemProduct1 = new ProductKitItemProduct();
        $product1 = (new ProductStub())
            ->setId(10);
        $productKitItemProduct1
            ->setProduct($product1);
        $kitItem->addKitItemProduct($productKitItemProduct1);

        $productKitItemProduct2 = new ProductKitItemProduct();
        $product2 = (new ProductStub())
            ->setId(20);
        $productKitItemProduct2
            ->setProduct($product2);
        $kitItem->addKitItemProduct($productKitItemProduct2);

        self::assertEquals([$product1, $product2], $kitItem->getProducts()->toArray());
    }
}
