<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Context;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PaymentBundle\Context\PaymentKitItemLineItem;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PaymentLineItemTest extends TestCase
{
    private const QUANTITY = 15;

    private Price|MockObject $price;

    private ProductUnit|MockObject $productUnit;

    private ProductHolderInterface|MockObject $productHolder;

    private Product|MockObject $product;

    #[\Override]
    protected function setUp(): void
    {
        $this->price = Price::create(123, 'USD');

        $this->productUnit = $this->createMock(ProductUnit::class);
        $this->productUnit->method('getCode')->willReturn('productUnitCode');

        $this->productHolder = $this->createMock(ProductHolderInterface::class);
        $this->productHolder->expects(self::any())
            ->method('getEntityIdentifier')
            ->willReturn('someId');

        $this->product = (new ProductStub())
            ->setId(1)
            ->setSku('someSku');
    }

    public function testOnlyRequiredParameters(): void
    {
        $paymentLineItem = (new PaymentLineItem(
            $this->productUnit,
            self::QUANTITY,
            $this->productHolder
        ));

        self::assertSame($this->productUnit, $paymentLineItem->getProductUnit());
        self::assertEquals($this->productUnit->getCode(), $paymentLineItem->getProductUnitCode());
        self::assertEquals(self::QUANTITY, $paymentLineItem->getQuantity());
        self::assertSame($this->productHolder, $paymentLineItem->getProductHolder());
        self::assertEquals($this->productHolder->getEntityIdentifier(), $paymentLineItem->getEntityIdentifier());
        self::assertNull($paymentLineItem->getProduct());
        self::assertNull($paymentLineItem->getProductSku());
        self::assertNull($paymentLineItem->getPrice());
        self::assertEquals(new ArrayCollection([]), $paymentLineItem->getKitItemLineItems());
        self::assertEquals('', $paymentLineItem->getChecksum());
    }

    public function testFullSet(): void
    {
        $anotherProductUnitCode = 'anotherUnitCode';
        $anotherQuantity = 123.123;
        $anotherSku = 'anotherSku';
        $checksum = 'checksum_1';

        $paymentKitItemLineItems = new ArrayCollection([$this->createMock(PaymentKitItemLineItem::class)]);

        $paymentLineItem = (new PaymentLineItem(
            $this->productUnit,
            self::QUANTITY,
            $this->productHolder
        ))
            ->setProductUnitCode($anotherProductUnitCode)
            ->setQuantity($anotherQuantity)
            ->setProduct($this->product)
            ->setProductSku($anotherSku)
            ->setPrice($this->price)
            ->setKitItemLineItems($paymentKitItemLineItems)
            ->setChecksum($checksum);

        self::assertSame($this->productUnit, $paymentLineItem->getProductUnit());
        self::assertEquals($anotherProductUnitCode, $paymentLineItem->getProductUnitCode());
        self::assertEquals($anotherQuantity, $paymentLineItem->getQuantity());
        self::assertSame($this->productHolder, $paymentLineItem->getProductHolder());
        self::assertEquals($this->productHolder->getEntityIdentifier(), $paymentLineItem->getEntityIdentifier());
        self::assertSame($paymentKitItemLineItems, $paymentLineItem->getKitItemLineItems());
        self::assertEquals($checksum, $paymentLineItem->getChecksum());
        self::assertSame($this->product, $paymentLineItem->getProduct());
        self::assertEquals($anotherSku, $paymentLineItem->getProductSku());
        self::assertSame($this->price, $paymentLineItem->getPrice());
    }
}
