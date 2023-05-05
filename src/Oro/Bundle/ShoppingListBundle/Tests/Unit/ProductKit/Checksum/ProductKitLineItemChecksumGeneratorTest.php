<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\ProductKit\Checksum;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;
use Oro\Bundle\ShoppingListBundle\ProductKit\Checksum\ProductKitLineItemChecksumGenerator;
use PHPUnit\Framework\TestCase;

class ProductKitLineItemChecksumGeneratorTest extends TestCase
{
    private ProductKitLineItemChecksumGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new ProductKitLineItemChecksumGenerator();
    }

    public function testGetChecksumWhenNoProduct(): void
    {
        self::assertNull($this->generator->getChecksum(new LineItem()));
    }

    public function testGetChecksumWhenNotKit(): void
    {
        $lineItem = (new LineItem())
            ->setProduct(new Product());

        self::assertNull($this->generator->getChecksum($lineItem));
    }

    public function testGetChecksumWhenNoKitItemLineItems(): void
    {
        $product = (new ProductStub())
            ->setId(42)
            ->setType(Product::TYPE_KIT);
        $productUnit = (new ProductUnit())->setCode('item');
        $lineItem = (new LineItem())
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
        $kitItemLineItem1 = (new ProductKitItemLineItem())
            ->setKitItem($kitItem1)
            ->setProduct($kitItem1Product)
            ->setQuantity(11)
            ->setUnit($productUnitSet);

        $kitItem2 = new ProductKitItemStub(20);
        $kitItem2Product = (new ProductStub())->setId(424242);
        $productUnitEach = (new ProductUnit())->setCode('each');
        $kitItemLineItem2 = (new ProductKitItemLineItem())
            ->setKitItem($kitItem2)
            ->setProduct($kitItem2Product)
            ->setQuantity(22)
            ->setUnit($productUnitEach);

        $lineItem = (new LineItem())
            ->setProduct($product)
            ->setUnit($productUnitItem)
            ->addKitItemLineItem($kitItemLineItem1)
            ->addKitItemLineItem($kitItemLineItem2);

        self::assertEquals('42|item|10|4242|11|set|20|424242|22|each', $this->generator->getChecksum($lineItem));
    }
}
