<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\ProductKit\Checksum;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\OrderBundle\ProductKit\Checksum\OrderProductKitLineItemChecksumGenerator;
use Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM\Stub\OrderLineItemStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductKitItemLineItemsAwareStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;

class OrderProductKitLineItemChecksumGeneratorTest extends TestCase
{
    private OrderProductKitLineItemChecksumGenerator $generator;

    #[\Override]
    protected function setUp(): void
    {
        $this->generator = new OrderProductKitLineItemChecksumGenerator();
    }

    public function testGetChecksumWhenNoProduct(): void
    {
        self::assertNull($this->generator->getChecksum(new OrderLineItemStub(1)));
    }

    public function testGetChecksumWhenNotOrderLineItem(): void
    {
        $lineItem = (new ProductKitItemLineItemsAwareStub(1))
            ->setProduct((new Product())->setType(Product::TYPE_KIT));

        self::assertNull($this->generator->getChecksum($lineItem));
    }

    public function testGetChecksumWhenNoKitItemLineItems(): void
    {
        $product = (new ProductStub())
            ->setId(42)
            ->setType(Product::TYPE_KIT);
        $productUnit = (new ProductUnit())->setCode('item');
        $lineItem = (new OrderLineItemStub(1))
            ->setProduct($product)
            ->setProductUnit($productUnit);

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
        $kitItemLineItem1 = (new OrderProductKitItemLineItem())
            ->setKitItem($kitItem1)
            ->setProduct($kitItem1Product)
            ->setQuantity(11)
            ->setProductUnit($productUnitSet)
            ->setPrice(Price::create(12.3456, 'USD'));

        $kitItem2 = new ProductKitItemStub(20);
        $kitItem2Product = (new ProductStub())->setId(424242);
        $productUnitEach = (new ProductUnit())->setCode('each');
        $kitItemLineItem2 = (new OrderProductKitItemLineItem())
            ->setKitItem($kitItem2)
            ->setProduct($kitItem2Product)
            ->setQuantity(22)
            ->setProductUnit($productUnitEach)
            ->setPrice(Price::create(34.5678, 'USD'));

        $lineItem = (new OrderLineItemStub(1))
            ->setProduct($product)
            ->setProductUnit($productUnitItem)
            ->addKitItemLineItem($kitItemLineItem1)
            ->addKitItemLineItem($kitItemLineItem2);

        self::assertEquals(
            '42|item|20|424242|22|each|34.5678|USD|10|4242|11|set|12.3456|USD',
            $this->generator->getChecksum($lineItem)
        );
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
        $kitItemLineItem1 = (new OrderProductKitItemLineItem())
            ->setKitItem($kitItem1)
            ->setProduct($kitItem1Product)
            ->setQuantity(11)
            ->setProductUnit($productUnitSet)
            ->setSortOrder(20)
            ->setPrice(Price::create(12.3456, 'USD'));

        $kitItem2 = new ProductKitItemStub(20);
        $kitItem2Product = (new ProductStub())->setId(424242);
        $productUnitEach = (new ProductUnit())->setCode('each');
        $kitItemLineItem2 = (new OrderProductKitItemLineItem())
            ->setKitItem($kitItem2)
            ->setProduct($kitItem2Product)
            ->setQuantity(22)
            ->setProductUnit($productUnitEach)
            ->setSortOrder(10)
            ->setPrice(Price::create(34.5678, 'USD'));

        $lineItem = (new OrderLineItemStub(1))
            ->setProduct($product)
            ->setProductUnit($productUnitItem)
            ->addKitItemLineItem($kitItemLineItem2)
            ->addKitItemLineItem($kitItemLineItem1);

        self::assertEquals(
            '42|item|20|424242|22|each|34.5678|USD|10|4242|11|set|12.3456|USD',
            $this->generator->getChecksum($lineItem)
        );
    }

    public function testGetChecksumWhenHasKitItemLineItemsAndNotDependOnEntities(): void
    {
        $product = (new ProductStub())
            ->setId(42)
            ->setType(Product::TYPE_KIT);
        $productUnitItem = (new ProductUnit())->setCode('item');

        $kitItem1 = new ProductKitItemStub(10);
        $productUnitSet = (new ProductUnit())->setCode('set');
        $kitItemLineItem1 = (new OrderProductKitItemLineItem())
            ->setQuantity(11)
            ->setSortOrder(20)
            ->setPrice(Price::create(12.3456, 'USD'));

        ReflectionUtil::setPropertyValue($kitItemLineItem1, 'kitItemId', $kitItem1->getId());
        ReflectionUtil::setPropertyValue($kitItemLineItem1, 'productId', $product->getId());
        ReflectionUtil::setPropertyValue($kitItemLineItem1, 'productUnitCode', $productUnitSet->getCode());

        $lineItem = (new OrderLineItemStub(1))
            ->setProduct($product)
            ->addKitItemLineItem($kitItemLineItem1);

        ReflectionUtil::setPropertyValue($lineItem, 'productUnitCode', $productUnitItem->getCode());

        self::assertEquals('42|item|10|42|11|set|12.3456|USD', $this->generator->getChecksum($lineItem));
    }
}
