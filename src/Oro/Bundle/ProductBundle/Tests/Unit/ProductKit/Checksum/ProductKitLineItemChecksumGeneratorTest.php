<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\ProductKit\Checksum;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\ProductKit\Checksum\ProductKitLineItemChecksumGenerator;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductKitItemLineItemsAwareStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductKitItemLineItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;

class ProductKitLineItemChecksumGeneratorTest extends TestCase
{
    private ProductKitLineItemChecksumGenerator $generator;

    #[\Override]
    protected function setUp(): void
    {
        $this->generator = new ProductKitLineItemChecksumGenerator();
    }

    public function testGetChecksumWhenNoProduct(): void
    {
        self::assertNull($this->generator->getChecksum(new ProductKitItemLineItemsAwareStub(1)));
    }

    public function testGetChecksumWhenNotKit(): void
    {
        $lineItem = (new ProductKitItemLineItemsAwareStub(1))
            ->setProduct(new Product());

        self::assertNull($this->generator->getChecksum($lineItem));
    }

    public function testGetChecksumWhenNoKitItemLineItems(): void
    {
        $product = (new ProductStub())
            ->setId(42)
            ->setType(Product::TYPE_KIT);
        $productUnit = (new ProductUnit())->setCode('item');
        $lineItem = (new ProductKitItemLineItemsAwareStub(1))
            ->setProduct($product)
            ->setUnit($productUnit);

        self::assertEquals('42|item', $this->generator->getChecksum($lineItem));
    }

    public function testGetChecksumWhenHasKitItemLineItems(): void
    {
        $product = (new ProductStub())
            ->setId(42)
            ->setType(Product::TYPE_KIT);
        $productUnitItem = (new ProductUnit())->setCode('item');

        $kitItem1 = new ProductKitItemStub(10);
        $kitItem1Product = (new ProductStub())->setId(4242);
        $productUnitSet = (new ProductUnit())->setCode('set');
        $kitItemLineItem1 = (new ProductKitItemLineItemStub(2))
            ->setKitItem($kitItem1)
            ->setProduct($kitItem1Product)
            ->setQuantity(11)
            ->setUnit($productUnitSet);

        $kitItem2 = new ProductKitItemStub(20);
        $kitItem2Product = (new ProductStub())->setId(424242);
        $productUnitEach = (new ProductUnit())->setCode('each');
        $kitItemLineItem2 = (new ProductKitItemLineItemStub(1))
            ->setKitItem($kitItem2)
            ->setProduct($kitItem2Product)
            ->setQuantity(22)
            ->setUnit($productUnitEach);

        $lineItem = (new ProductKitItemLineItemsAwareStub(1))
            ->setProduct($product)
            ->setUnit($productUnitItem)
            ->addKitItemLineItem($kitItemLineItem1)
            ->addKitItemLineItem($kitItemLineItem2);

        self::assertEquals('42|item|20|424242|22|each|10|4242|11|set', $this->generator->getChecksum($lineItem));
    }

    public function testGetChecksumWhenHasKitItemLineItemsAndNotDependOnOrder(): void
    {
        $product = (new ProductStub())
            ->setId(42)
            ->setType(Product::TYPE_KIT);
        $productUnitItem = (new ProductUnit())->setCode('item');

        $kitItem1 = new ProductKitItemStub(10);
        $kitItem1Product = (new ProductStub())->setId(4242);
        $productUnitSet = (new ProductUnit())->setCode('set');
        $kitItemLineItem1 = (new ProductKitItemLineItemStub(2))
            ->setKitItem($kitItem1)
            ->setProduct($kitItem1Product)
            ->setQuantity(11)
            ->setUnit($productUnitSet)
            ->setSortOrder(20);

        $kitItem2 = new ProductKitItemStub(20);
        $kitItem2Product = (new ProductStub())->setId(424242);
        $productUnitEach = (new ProductUnit())->setCode('each');
        $kitItemLineItem2 = (new ProductKitItemLineItemStub(1))
            ->setKitItem($kitItem2)
            ->setProduct($kitItem2Product)
            ->setQuantity(22)
            ->setUnit($productUnitEach)
            ->setSortOrder(10);

        $lineItem = (new ProductKitItemLineItemsAwareStub(1))
            ->setProduct($product)
            ->setUnit($productUnitItem)
            ->addKitItemLineItem($kitItemLineItem2)
            ->addKitItemLineItem($kitItemLineItem1);

        self::assertEquals('42|item|20|424242|22|each|10|4242|11|set', $this->generator->getChecksum($lineItem));
    }

    public function testGetChecksumWhenHasKitItemLineItemsAndNotDependOnProductUnitEntity(): void
    {
        $product = (new ProductStub())
            ->setId(42)
            ->setType(Product::TYPE_KIT);
        $productUnitItem = (new ProductUnit())->setCode('item');

        $kitItem1 = new ProductKitItemStub(10);
        $kitItem1Product = (new ProductStub())->setId(4242);
        $productUnitSet = (new ProductUnit())->setCode('set');
        $kitItemLineItem1 = (new ProductKitItemLineItemStub(2))
            ->setKitItem($kitItem1)
            ->setProduct($kitItem1Product)
            ->setQuantity(11)
            ->setSortOrder(20);

        ReflectionUtil::setPropertyValue($kitItemLineItem1, 'unitCode', $productUnitSet->getCode());

        $lineItem = (new ProductKitItemLineItemsAwareStub(1))
            ->setProduct($product)
            ->addKitItemLineItem($kitItemLineItem1);

        ReflectionUtil::setPropertyValue($lineItem, 'unitCode', $productUnitItem->getCode());

        self::assertEquals('42|item|10|4242|11|set', $this->generator->getChecksum($lineItem));
    }
}
