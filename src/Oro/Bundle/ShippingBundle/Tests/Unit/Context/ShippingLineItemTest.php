<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ShippingBundle\Context\ShippingKitItemLineItem;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShippingLineItemTest extends TestCase
{
    private const QUANTITY = 15;

    private ProductUnit|MockObject $productUnit;

    private ProductHolderInterface|MockObject $productHolder;

    private Product|MockObject $product;

    private Price|MockObject $price;

    private Weight|MockObject $weight;

    private Dimensions|MockObject $dimensions;

    #[\Override]
    protected function setUp(): void
    {
        $this->productUnit = $this->createMock(ProductUnit::class);
        $this->productUnit->method('getCode')->willReturn('productUnitCode');

        $this->productHolder = $this->createMock(ProductHolderInterface::class);
        $this->productHolder->method('getEntityIdentifier')->willReturn('entityId');

        $this->product = (new ProductStub())
            ->setId(1)
            ->setSku('someSku');

        $this->price = $this->createMock(Price::class);

        $this->dimensions = $this->createMock(Dimensions::class);

        $this->weight = $this->createMock(Weight::class);
    }

    public function testOnlyRequiredParameters(): void
    {
        $shippingLineItem = (new ShippingLineItem(
            $this->productUnit,
            self::QUANTITY,
            $this->productHolder
        ));

        self::assertSame($this->productUnit, $shippingLineItem->getProductUnit());
        self::assertEquals($this->productUnit->getCode(), $shippingLineItem->getProductUnitCode());
        self::assertEquals(self::QUANTITY, $shippingLineItem->getQuantity());
        self::assertSame($this->productHolder, $shippingLineItem->getProductHolder());
        self::assertEquals($this->productHolder->getEntityIdentifier(), $shippingLineItem->getEntityIdentifier());
        self::assertNull($shippingLineItem->getProduct());
        self::assertNull($shippingLineItem->getProductSku());
        self::assertNull($shippingLineItem->getPrice());
        self::assertNull($shippingLineItem->getDimensions());
        self::assertNull($shippingLineItem->getWeight());
        self::assertEquals(new ArrayCollection([]), $shippingLineItem->getKitItemLineItems());
        self::assertEquals('', $shippingLineItem->getChecksum());
    }

    public function testFullSet(): void
    {
        $anotherProductUnitCode = 'anotherUnitCode';
        $anotherQuantity = 123.123;
        $anotherSku = 'anotherSku';
        $checksum = 'checksum_1';

        $shippingKitItemLineItems = new ArrayCollection([$this->createMock(ShippingKitItemLineItem::class)]);

        $shippingLineItem = (new ShippingLineItem(
            $this->productUnit,
            self::QUANTITY,
            $this->productHolder
        ))
            ->setProductUnitCode($anotherProductUnitCode)
            ->setQuantity($anotherQuantity)
            ->setPrice($this->price)
            ->setProduct($this->product)
            ->setProductSku($anotherSku)
            ->setDimensions($this->dimensions)
            ->setWeight($this->weight)
            ->setKitItemLineItems($shippingKitItemLineItems)
            ->setChecksum($checksum);

        self::assertSame($this->productUnit, $shippingLineItem->getProductUnit());
        self::assertEquals($anotherProductUnitCode, $shippingLineItem->getProductUnitCode());
        self::assertEquals($anotherQuantity, $shippingLineItem->getQuantity());
        self::assertSame($this->productHolder, $shippingLineItem->getProductHolder());
        self::assertEquals($this->productHolder->getEntityIdentifier(), $shippingLineItem->getEntityIdentifier());
        self::assertSame($this->product, $shippingLineItem->getProduct());
        self::assertEquals($anotherSku, $shippingLineItem->getProductSku());
        self::assertSame($this->price, $shippingLineItem->getPrice());
        self::assertSame($this->dimensions, $shippingLineItem->getDimensions());
        self::assertSame($this->weight, $shippingLineItem->getWeight());
        self::assertSame($shippingKitItemLineItems, $shippingLineItem->getKitItemLineItems());
        self::assertEquals($checksum, $shippingLineItem->getChecksum());
    }
}
