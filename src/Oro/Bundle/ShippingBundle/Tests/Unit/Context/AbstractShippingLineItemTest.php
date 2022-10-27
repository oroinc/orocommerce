<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;

abstract class AbstractShippingLineItemTest extends \PHPUnit\Framework\TestCase
{
    const TEST_UNIT_CODE = 'someCode';
    const TEST_QUANTITY = 15;
    const TEST_PRODUCT_SKU = 'someSku';
    const TEST_PRODUCT_ID = 1;
    const TEST_ENTITY_ID = 'someId';

    /**
     * @var Price|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $priceMock;

    /**
     * @var ProductUnit|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $productUnitMock;

    /**
     * @var ProductHolderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $productHolderMock;

    /**
     * @var Weight
     */
    protected $weightMock;

    /**
     * @var Product
     */
    protected $productMock;

    /**
     * @var Dimensions
     */
    protected $dimensionsMock;

    protected function setUp(): void
    {
        $this->priceMock = $this->createMock(Price::class);

        $this->productUnitMock = $this->createMock(ProductUnit::class);

        $this->productUnitMock->method('getCode')->willReturn(static::TEST_UNIT_CODE);

        $this->productHolderMock = $this->createMock(ProductHolderInterface::class);

        $this->productHolderMock->method('getEntityIdentifier')->willReturn(static::TEST_ENTITY_ID);

        $this->dimensionsMock = $this->createMock(Dimensions::class);

        $this->productMock = $this->createMock(Product::class);

        $this->productMock->method('getSku')->willReturn(static::TEST_PRODUCT_SKU);
        $this->productMock->method('getId')->willReturn(static::TEST_PRODUCT_ID);

        $this->weightMock = $this->createMock(Weight::class);
    }

    /**
     * @return array
     */
    protected function getShippingLineItemParams()
    {
        return [
            ShippingLineItem::FIELD_PRICE => $this->priceMock,
            ShippingLineItem::FIELD_PRODUCT_UNIT => $this->productUnitMock,
            ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => self::TEST_UNIT_CODE,
            ShippingLineItem::FIELD_QUANTITY => self::TEST_QUANTITY,
            ShippingLineItem::FIELD_PRODUCT_HOLDER => $this->productHolderMock,
            ShippingLineItem::FIELD_PRODUCT => $this->productMock,
            ShippingLineItem::FIELD_PRODUCT_SKU => self::TEST_PRODUCT_SKU,
            ShippingLineItem::FIELD_DIMENSIONS => $this->dimensionsMock,
            ShippingLineItem::FIELD_WEIGHT => $this->weightMock,
            ShippingLineItem::FIELD_ENTITY_IDENTIFIER => self::TEST_ENTITY_ID,
        ];
    }
}
