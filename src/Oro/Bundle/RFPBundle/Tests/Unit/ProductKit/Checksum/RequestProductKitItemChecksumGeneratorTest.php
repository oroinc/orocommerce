<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Tests\Unit\ProductKit\Checksum;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductKitItemLineItemsAwareStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductKitItemLineItem;
use Oro\Bundle\RFPBundle\ProductKit\Checksum\RequestProductKitItemChecksumGenerator;
use Oro\Bundle\RFPBundle\Tests\Unit\Stub\RequestProductItemStub;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;

class RequestProductKitItemChecksumGeneratorTest extends TestCase
{
    private RequestProductKitItemChecksumGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new RequestProductKitItemChecksumGenerator();
    }

    public function testGetChecksumWhenNoProduct(): void
    {
        self::assertNull($this->generator->getChecksum(new RequestProductItemStub(1)));
    }

    public function testGetChecksumWhenNotRequestProductItem(): void
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
        $requestProduct = (new RequestProduct())
            ->setProduct($product);
        $productUnit = (new ProductUnit())->setCode('item');
        $lineItem = (new RequestProductItemStub(1))
            ->setRequestProduct($requestProduct)
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
        $kitItemLineItem1 = (new RequestProductKitItemLineItem())
            ->setKitItem($kitItem1)
            ->setProduct($kitItem1Product)
            ->setQuantity(11)
            ->setProductUnit($productUnitSet);

        $kitItem2 = new ProductKitItemStub(20);
        $kitItem2Product = (new ProductStub())->setId(424242);
        $productUnitEach = (new ProductUnit())->setCode('each');
        $kitItemLineItem2 = (new RequestProductKitItemLineItem())
            ->setKitItem($kitItem2)
            ->setProduct($kitItem2Product)
            ->setQuantity(22)
            ->setProductUnit($productUnitEach);

        $requestProduct = (new RequestProduct())
            ->setProduct($product)
            ->addKitItemLineItem($kitItemLineItem1)
            ->addKitItemLineItem($kitItemLineItem2);

        $lineItem = (new RequestProductItemStub(1))
            ->setRequestProduct($requestProduct)
            ->setProductUnit($productUnitItem);

        self::assertEquals(
            '42|item|20|424242|22|each|10|4242|11|set',
            $this->generator->getChecksum($lineItem)
        );
    }

    public function testGetChecksumWhenHasNotLoadedKitItemLineItems(): void
    {
        $product = (new ProductStub())
            ->setId(42)
            ->setType(Product::TYPE_KIT);
        $productUnitItem = (new ProductUnit())->setCode('item');

        $kitItem1 = new ProductKitItemStub(10);
        $kitItem1Product = (new ProductStub())->setId(4242);
        $productUnitSet = (new ProductUnit())->setCode('set');
        $kitItemLineItem1 = (new RequestProductKitItemLineItem())
            ->setKitItem($kitItem1)
            ->setProduct($kitItem1Product)
            ->setQuantity(11)
            ->setProductUnit($productUnitSet);

        $kitItem2 = new ProductKitItemStub(20);
        $kitItem2Product = (new ProductStub())->setId(424242);
        $productUnitEach = (new ProductUnit())->setCode('each');
        $kitItemLineItem2 = (new RequestProductKitItemLineItem())
            ->setKitItem($kitItem2)
            ->setProduct($kitItem2Product)
            ->setQuantity(22)
            ->setProductUnit($productUnitEach);

        $requestProduct = (new RequestProduct())
            ->setProduct($product)
            ->addKitItemLineItem($kitItemLineItem1)
            ->addKitItemLineItem($kitItemLineItem2);

        $lineItem = (new RequestProductItemStub(1))
            ->setRequestProduct($requestProduct)
            ->setProductUnit($productUnitItem);

        ReflectionUtil::setPropertyValue($lineItem, 'kitItemLineItems', new ArrayCollection());

        self::assertEquals(
            '42|item|20|424242|22|each|10|4242|11|set',
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
        $kitItemLineItem1 = (new RequestProductKitItemLineItem())
            ->setKitItem($kitItem1)
            ->setProduct($kitItem1Product)
            ->setQuantity(11)
            ->setProductUnit($productUnitSet)
            ->setSortOrder(20);

        $kitItem2 = new ProductKitItemStub(20);
        $kitItem2Product = (new ProductStub())->setId(424242);
        $productUnitEach = (new ProductUnit())->setCode('each');
        $kitItemLineItem2 = (new RequestProductKitItemLineItem())
            ->setKitItem($kitItem2)
            ->setProduct($kitItem2Product)
            ->setQuantity(22)
            ->setProductUnit($productUnitEach)
            ->setSortOrder(10);

        $requestProduct = (new RequestProduct())
            ->setProduct($product)
            ->addKitItemLineItem($kitItemLineItem2)
            ->addKitItemLineItem($kitItemLineItem1);

        $lineItem = (new RequestProductItemStub(1))
            ->setRequestProduct($requestProduct)
            ->setProductUnit($productUnitItem);

        self::assertEquals(
            '42|item|20|424242|22|each|10|4242|11|set',
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
        $kitItemLineItem1 = (new RequestProductKitItemLineItem())
            ->setQuantity(11)
            ->setSortOrder(20);

        ReflectionUtil::setPropertyValue($kitItemLineItem1, 'kitItemId', $kitItem1->getId());
        ReflectionUtil::setPropertyValue($kitItemLineItem1, 'productId', $product->getId());
        ReflectionUtil::setPropertyValue($kitItemLineItem1, 'productUnitCode', $productUnitSet->getCode());

        $requestProduct = (new RequestProduct())
            ->setProduct($product)
            ->addKitItemLineItem($kitItemLineItem1);

        $lineItem = (new RequestProductItemStub(1))
            ->setRequestProduct($requestProduct);

        ReflectionUtil::setPropertyValue($lineItem, 'productUnitCode', $productUnitItem->getCode());

        self::assertEquals('42|item|10|42|11|set', $this->generator->getChecksum($lineItem));
    }
}
