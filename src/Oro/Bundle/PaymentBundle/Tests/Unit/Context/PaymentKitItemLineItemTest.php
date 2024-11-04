<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Context;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PaymentBundle\Context\PaymentKitItemLineItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PaymentKitItemLineItemTest extends TestCase
{
    private const QUANTITY = 15;

    private ProductUnit|MockObject $productUnit;

    private ProductHolderInterface|MockObject $productHolder;

    #[\Override]
    protected function setUp(): void
    {
        $this->productUnit = $this->createMock(ProductUnit::class);
        $this->productUnit->method('getCode')->willReturn('productUnitCode');

        $this->productHolder = $this->createMock(ProductHolderInterface::class);
        $this->productHolder->method('getEntityIdentifier')->willReturn('someId');
    }

    public function testOnlyRequiredParameters(): void
    {
        $paymentKitItemLineItem = (new PaymentKitItemLineItem(
            $this->productUnit,
            self::QUANTITY,
            $this->productHolder
        ));

        self::assertSame($this->productUnit, $paymentKitItemLineItem->getProductUnit());
        self::assertEquals($this->productUnit->getCode(), $paymentKitItemLineItem->getProductUnitCode());
        self::assertEquals(self::QUANTITY, $paymentKitItemLineItem->getQuantity());
        self::assertSame($this->productHolder, $paymentKitItemLineItem->getProductHolder());
        self::assertEquals($this->productHolder->getEntityIdentifier(), $paymentKitItemLineItem->getEntityIdentifier());
        self::assertNull($paymentKitItemLineItem->getProduct());
        self::assertNull($paymentKitItemLineItem->getProductSku());
        self::assertNull($paymentKitItemLineItem->getPrice());
        self::assertNull($paymentKitItemLineItem->getKitItem());
        self::assertEquals(0, $paymentKitItemLineItem->getSortOrder());
    }

    public function testFullSet(): void
    {
        $anotherUnitCode = 'anotherUnitCode';
        $anotherQuantity = 13;
        $anotherSku = 'anotherSku';
        $sortOrder = 2;
        $price = Price::create(123, 'USD');
        $product = (new ProductStub())
            ->setId(1)
            ->setSku('someSku');

        $kitItem = new ProductKitItem();

        $paymentKitItemLineItem = (new PaymentKitItemLineItem(
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

        self::assertSame($this->productUnit, $paymentKitItemLineItem->getProductUnit());
        self::assertEquals($anotherUnitCode, $paymentKitItemLineItem->getProductUnitCode());
        self::assertEquals($anotherQuantity, $paymentKitItemLineItem->getQuantity());
        self::assertSame($this->productHolder, $paymentKitItemLineItem->getProductHolder());
        self::assertEquals($this->productHolder->getEntityIdentifier(), $paymentKitItemLineItem->getEntityIdentifier());
        self::assertSame($product, $paymentKitItemLineItem->getProduct());
        self::assertEquals($anotherSku, $paymentKitItemLineItem->getProductSku());
        self::assertSame($price, $paymentKitItemLineItem->getPrice());
        self::assertEquals($kitItem, $paymentKitItemLineItem->getKitItem());
        self::assertEquals($sortOrder, $paymentKitItemLineItem->getSortOrder());
    }
}
