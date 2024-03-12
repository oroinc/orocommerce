<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ShippingBundle\Context\ShippingKitItemLineItem;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShippingKitItemLineItemTest extends TestCase
{
    private ProductUnit|MockObject $productUnit;

    private ProductHolderInterface|MockObject $productHolder;

    private Weight|MockObject $weight;

    private Dimensions|MockObject $dimensions;

    protected function setUp(): void
    {
        $this->productUnit = $this->createMock(ProductUnit::class);
        $this->productUnit->method('getCode')->willReturn('productUnitCode');

        $this->productHolder = $this->createMock(ProductHolderInterface::class);
        $this->productHolder->method('getEntityIdentifier')->willReturn('entityId');

        $this->dimensions = $this->createMock(Dimensions::class);
        $this->weight = $this->createMock(Weight::class);
    }

    public function testOnlyRequiredParameters(): void
    {
        $shippingKitItemLineItem = new ShippingKitItemLineItem($this->productHolder);

        self::assertSame($this->productHolder, $shippingKitItemLineItem->getProductHolder());
        self::assertEquals(
            $this->productHolder->getEntityIdentifier(),
            $shippingKitItemLineItem->getEntityIdentifier()
        );
        self::assertNull($shippingKitItemLineItem->getProduct());
        self::assertNull($shippingKitItemLineItem->getProductSku());
        self::assertNull($shippingKitItemLineItem->getProductUnit());
        self::assertNull($shippingKitItemLineItem->getProductUnitCode());
        self::assertEquals(0, $shippingKitItemLineItem->getQuantity());
        self::assertNull($shippingKitItemLineItem->getPrice());
        self::assertNull($shippingKitItemLineItem->getKitItem());
        self::assertEquals(0, $shippingKitItemLineItem->getSortOrder());
    }

    public function testFullSet(): void
    {
        $product = (new ProductStub())
            ->setId(1)
            ->setSku('sku1');
        $price = Price::create(1, 'USD');
        $kitItem = new ProductKitItem();
        $sortOrder = 1;
        $anotherQuantity = 123.123;
        $anotherSku = 'anotherSku';
        $anotherUnitCode = 'anotherUnitCode';

        $shippingKitItemLineItem = (new ShippingKitItemLineItem($this->productHolder))
            ->setProductUnit($this->productUnit)
            ->setProductUnitCode($anotherUnitCode)
            ->setQuantity($anotherQuantity)
            ->setProduct($product)
            ->setProductSku($anotherSku)
            ->setPrice($price)
            ->setKitItem($kitItem)
            ->setDimensions($this->dimensions)
            ->setWeight($this->weight)
            ->setSortOrder($sortOrder);

        self::assertSame($this->productUnit, $shippingKitItemLineItem->getProductUnit());
        self::assertEquals($anotherUnitCode, $shippingKitItemLineItem->getProductUnitCode());
        self::assertEquals($anotherQuantity, $shippingKitItemLineItem->getQuantity());
        self::assertSame($this->productHolder, $shippingKitItemLineItem->getProductHolder());
        self::assertEquals(
            $this->productHolder->getEntityIdentifier(),
            $shippingKitItemLineItem->getEntityIdentifier()
        );
        self::assertSame($product, $shippingKitItemLineItem->getProduct());
        self::assertEquals($anotherSku, $shippingKitItemLineItem->getProductSku());
        self::assertSame($price, $shippingKitItemLineItem->getPrice());
        self::assertSame($kitItem, $shippingKitItemLineItem->getKitItem());
        self::assertEquals($sortOrder, $shippingKitItemLineItem->getSortOrder());
        self::assertSame($this->dimensions, $shippingKitItemLineItem->getDimensions());
        self::assertSame($this->weight, $shippingKitItemLineItem->getWeight());
    }
}
