<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Context;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;

class PaymentLineItemTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Price|\PHPUnit\Framework\MockObject\MockObject
     */
    private $priceMock;

    /**
     * @var ProductUnit|\PHPUnit\Framework\MockObject\MockObject
     */
    private $productUnitMock;

    /**
     * @var ProductHolderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $productHolderMock;

    /**
     * @var Product
     */
    private $productMock;

    protected function setUp(): void
    {
        $this->priceMock = $this->getMockBuilder(Price::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productUnitMock = $this->getMockBuilder(ProductUnit::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productHolderMock = $this->createMock(ProductHolderInterface::class);

        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testGetters()
    {
        $unitCode = 'someCode';
        $quantity = 15;
        $productSku = 'someSku';
        $entityIdentifier = 'someId';

        $paymentLineItemParams = [
            PaymentLineItem::FIELD_PRICE => $this->priceMock,
            PaymentLineItem::FIELD_PRODUCT_UNIT => $this->productUnitMock,
            PaymentLineItem::FIELD_PRODUCT_UNIT_CODE => $unitCode,
            PaymentLineItem::FIELD_QUANTITY => $quantity,
            PaymentLineItem::FIELD_PRODUCT_HOLDER => $this->productHolderMock,
            PaymentLineItem::FIELD_PRODUCT => $this->productMock,
            PaymentLineItem::FIELD_PRODUCT_SKU => $productSku,
            PaymentLineItem::FIELD_ENTITY_IDENTIFIER => $entityIdentifier,
        ];

        $paymentLineItem = new PaymentLineItem($paymentLineItemParams);

        static::assertEquals($paymentLineItemParams[PaymentLineItem::FIELD_PRICE], $paymentLineItem->getPrice());
        static::assertEquals(
            $paymentLineItemParams[PaymentLineItem::FIELD_PRODUCT_UNIT],
            $paymentLineItem->getProductUnit()
        );
        static::assertEquals(
            $paymentLineItemParams[PaymentLineItem::FIELD_PRODUCT_UNIT_CODE],
            $paymentLineItem->getProductUnitCode()
        );
        static::assertEquals(
            $paymentLineItemParams[PaymentLineItem::FIELD_QUANTITY],
            $paymentLineItem->getQuantity()
        );
        static::assertEquals(
            $paymentLineItemParams[PaymentLineItem::FIELD_PRODUCT_HOLDER],
            $paymentLineItem->getProductHolder()
        );
        static::assertEquals($paymentLineItemParams[PaymentLineItem::FIELD_PRODUCT], $paymentLineItem->getProduct());
        static::assertEquals(
            $paymentLineItemParams[PaymentLineItem::FIELD_PRODUCT_SKU],
            $paymentLineItem->getProductSku()
        );
        static::assertEquals(
            $paymentLineItemParams[PaymentLineItem::FIELD_ENTITY_IDENTIFIER],
            $paymentLineItem->getEntityIdentifier()
        );
    }
}
