<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class AbstractShippingLineItemTest extends TestCase
{
    protected const TEST_UNIT_CODE = 'someCode';
    protected const TEST_QUANTITY = 15;
    protected const TEST_PRODUCT_SKU = 'someSku';
    protected const TEST_PRODUCT_ID = 1;
    protected const TEST_ENTITY_ID = 'someId';

    protected Price $price;
    protected ProductUnit $productUnit;
    protected Product $product;
    protected Weight $weight;
    protected Dimensions $dimensions;
    protected ProductHolderInterface|MockObject $productHolder;

    protected function setUp(): void
    {
        $this->price = $this->createMock(Price::class);
        $this->productUnit = $this->createMock(ProductUnit::class);
        $this->productUnit->expects(self::any())
            ->method('getCode')
            ->willReturn(static::TEST_UNIT_CODE);
        $this->product = $this->createMock(Product::class);
        $this->product->expects(self::any())
            ->method('getSku')
            ->willReturn(static::TEST_PRODUCT_SKU);
        $this->product->expects(self::any())
            ->method('getId')
            ->willReturn(static::TEST_PRODUCT_ID);
        $this->dimensions = $this->createMock(Dimensions::class);
        $this->weight = $this->createMock(Weight::class);
        $this->productHolder = $this->createMock(ProductHolderInterface::class);
        $this->productHolder->expects(self::any())
            ->method('getEntityIdentifier')
            ->willReturn(static::TEST_ENTITY_ID);
    }

    protected function getShippingLineItemParams(): array
    {
        return [
            ShippingLineItem::FIELD_PRICE => $this->price,
            ShippingLineItem::FIELD_PRODUCT_UNIT => $this->productUnit,
            ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => self::TEST_UNIT_CODE,
            ShippingLineItem::FIELD_QUANTITY => self::TEST_QUANTITY,
            ShippingLineItem::FIELD_PRODUCT_HOLDER => $this->productHolder,
            ShippingLineItem::FIELD_PRODUCT => $this->product,
            ShippingLineItem::FIELD_PRODUCT_SKU => self::TEST_PRODUCT_SKU,
            ShippingLineItem::FIELD_DIMENSIONS => $this->dimensions,
            ShippingLineItem::FIELD_WEIGHT => $this->weight,
            ShippingLineItem::FIELD_ENTITY_IDENTIFIER => self::TEST_ENTITY_ID,
        ];
    }
}
