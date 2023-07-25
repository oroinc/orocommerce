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
    private const UNIT_CODE = 'unitCode';
    private const QUANTITY = 1;

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
            self::UNIT_CODE,
            self::QUANTITY,
            $this->productHolder
        ));

        self::assertSame($this->productUnit, $shippingKitItemLineItem->getProductUnit());
        self::assertEquals(self::UNIT_CODE, $shippingKitItemLineItem->getProductUnitCode());
        self::assertEquals(self::QUANTITY, $shippingKitItemLineItem->getQuantity());
        self::assertSame($this->productHolder, $shippingKitItemLineItem->getProductHolder());
        self::assertEquals(
            $this->productHolder->getEntityIdentifier(),
            $shippingKitItemLineItem->getEntityIdentifier()
        );
        self::assertNull($shippingKitItemLineItem->getProduct());
        self::assertNull($shippingKitItemLineItem->getProductSku());
        self::assertNull($shippingKitItemLineItem->getPrice());
        self::assertEquals(
            $this->productHolder->getEntityIdentifier(),
            $shippingKitItemLineItem->getEntityIdentifier()
        );
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

        $shippingKitItemLineItem = (new ShippingKitItemLineItem(
            $this->productUnit,
            self::UNIT_CODE,
            self::QUANTITY,
            $this->productHolder
        ))
            ->setQuantity($anotherQuantity)
            ->setProduct($product)
            ->setProductSku($anotherSku)
            ->setPrice($price)
            ->setKitItem($kitItem)
            ->setSortOrder($sortOrder);

        self::assertSame($this->productUnit, $shippingKitItemLineItem->getProductUnit());
        self::assertEquals(self::UNIT_CODE, $shippingKitItemLineItem->getProductUnitCode());
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
