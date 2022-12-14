<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Context;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;

class PaymentLineItemTest extends \PHPUnit\Framework\TestCase
{
    /** @var Price|\PHPUnit\Framework\MockObject\MockObject */
    private $price;

    /** @var ProductUnit|\PHPUnit\Framework\MockObject\MockObject */
    private $productUnit;

    /** @var ProductHolderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $productHolder;

    /** @var Product|\PHPUnit\Framework\MockObject\MockObject */
    private $product;

    protected function setUp(): void
    {
        $this->price = $this->createMock(Price::class);
        $this->productUnit = $this->createMock(ProductUnit::class);
        $this->productHolder = $this->createMock(ProductHolderInterface::class);
        $this->product = $this->createMock(Product::class);
    }

    public function testGetters()
    {
        $unitCode = 'someCode';
        $quantity = 15;
        $productSku = 'someSku';
        $entityIdentifier = 'someId';

        $paymentLineItemParams = [
            PaymentLineItem::FIELD_PRICE => $this->price,
            PaymentLineItem::FIELD_PRODUCT_UNIT => $this->productUnit,
            PaymentLineItem::FIELD_PRODUCT_UNIT_CODE => $unitCode,
            PaymentLineItem::FIELD_QUANTITY => $quantity,
            PaymentLineItem::FIELD_PRODUCT_HOLDER => $this->productHolder,
            PaymentLineItem::FIELD_PRODUCT => $this->product,
            PaymentLineItem::FIELD_PRODUCT_SKU => $productSku,
            PaymentLineItem::FIELD_ENTITY_IDENTIFIER => $entityIdentifier,
        ];

        $paymentLineItem = new PaymentLineItem($paymentLineItemParams);

        self::assertEquals($paymentLineItemParams[PaymentLineItem::FIELD_PRICE], $paymentLineItem->getPrice());
        self::assertEquals(
            $paymentLineItemParams[PaymentLineItem::FIELD_PRODUCT_UNIT],
            $paymentLineItem->getProductUnit()
        );
        self::assertEquals(
            $paymentLineItemParams[PaymentLineItem::FIELD_PRODUCT_UNIT_CODE],
            $paymentLineItem->getProductUnitCode()
        );
        self::assertEquals(
            $paymentLineItemParams[PaymentLineItem::FIELD_QUANTITY],
            $paymentLineItem->getQuantity()
        );
        self::assertEquals(
            $paymentLineItemParams[PaymentLineItem::FIELD_PRODUCT_HOLDER],
            $paymentLineItem->getProductHolder()
        );
        self::assertEquals($paymentLineItemParams[PaymentLineItem::FIELD_PRODUCT], $paymentLineItem->getProduct());
        self::assertEquals(
            $paymentLineItemParams[PaymentLineItem::FIELD_PRODUCT_SKU],
            $paymentLineItem->getProductSku()
        );
        self::assertEquals(
            $paymentLineItemParams[PaymentLineItem::FIELD_ENTITY_IDENTIFIER],
            $paymentLineItem->getEntityIdentifier()
        );
    }
}
