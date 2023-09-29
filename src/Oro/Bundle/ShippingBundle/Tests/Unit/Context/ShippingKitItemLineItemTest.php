<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ShippingBundle\Context\ShippingKitItemLineItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShippingKitItemLineItemTest extends TestCase
{
    private const QUANTITY = 15;

    private ProductUnit|MockObject $productUnit;

    private ProductHolderInterface|MockObject $productHolder;

    protected function setUp(): void
    {
        $this->productUnit = $this->createMock(ProductUnit::class);
        $this->productUnit->method('getCode')->willReturn('productUnitCode');

        $this->productHolder = $this->createMock(ProductHolderInterface::class);
        $this->productHolder->method('getEntityIdentifier')->willReturn('entityId');
    }

    public function testOnlyRequiredParameters(): void
    {
        $shippingKitItemLineItem = (new ShippingKitItemLineItem(
            $this->productUnit,
            self::QUANTITY,
            $this->productHolder
        ));

        self::assertSame($this->productUnit, $shippingKitItemLineItem->getProductUnit());
        self::assertEquals($this->productUnit->getCode(), $shippingKitItemLineItem->getProductUnitCode());
        self::assertEquals(self::QUANTITY, $shippingKitItemLineItem->getQuantity());
        self::assertSame($this->productHolder, $shippingKitItemLineItem->getProductHolder());
        self::assertEquals(
            $this->productHolder->getEntityIdentifier(),
            $shippingKitItemLineItem->getEntityIdentifier()
        );
        self::assertNull($shippingKitItemLineItem->getProduct());
        self::assertNull($shippingKitItemLineItem->getProductSku());
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

        $shippingKitItemLineItem = (new ShippingKitItemLineItem(
            $this->productUnit,
            self::QUANTITY,
            $this->productHolder
        ))
            ->setProductUnitCode($anotherUnitCode)
            ->setQuantity($anotherQuantity)
            ->setProduct($product)
            ->setProductSku($anotherSku)
            ->setPrice($price)
            ->setKitItem($kitItem)
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
    }
}
