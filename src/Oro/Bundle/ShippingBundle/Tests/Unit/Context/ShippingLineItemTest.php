<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;

class ShippingLineItemTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Price|\PHPUnit_Framework_MockObject_MockObject
     */
    private $priceMock;

    /**
     * @var ProductUnit|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productUnitMock;

    /**
     * @var ProductHolderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productHolderMock;

    /**
     * @var Weight
     */
    private $weightMock;

    /**
     * @var Product
     */
    private $productMock;

    /**
     * @var Dimensions
     */
    private $dimensionsMock;

    public function setUp()
    {
        $this->priceMock = $this->getMockBuilder(Price::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productUnitMock = $this->getMockBuilder(ProductUnit::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productHolderMock = $this->getMock(ProductHolderInterface::class);

        $this->dimensionsMock = $this->getMockBuilder(Dimensions::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->weightMock = $this->getMockBuilder(Weight::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testGetters()
    {
        $unitCode = 'someCode';
        $quantity = 15;
        $productSku = 'someSku';
        $entityIdentifier = 'someId';

        $shippingLineItemParams = [
            ShippingLineItem::FIELD_PRICE => $this->priceMock,
            ShippingLineItem::FIELD_PRODUCT_UNIT => $this->productUnitMock,
            ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => $unitCode,
            ShippingLineItem::FIELD_QUANTITY => $quantity,
            ShippingLineItem::FIELD_PRODUCT_HOLDER => $this->productHolderMock,
            ShippingLineItem::FIELD_PRODUCT => $this->productMock,
            ShippingLineItem::FIELD_PRODUCT_SKU => $productSku,
            ShippingLineItem::FIELD_DIMENSIONS => $this->dimensionsMock,
            ShippingLineItem::FIELD_WEIGHT => $this->weightMock,
            ShippingLineItem::FIELD_ENTITY_IDENTIFIER => $entityIdentifier,
        ];

        $shippingLineItem = new ShippingLineItem($shippingLineItemParams);

        $this->assertEquals($shippingLineItemParams[ShippingLineItem::FIELD_PRICE], $shippingLineItem->getPrice());
        $this->assertEquals(
            $shippingLineItemParams[ShippingLineItem::FIELD_PRODUCT_UNIT],
            $shippingLineItem->getProductUnit()
        );
        $this->assertEquals(
            $shippingLineItemParams[ShippingLineItem::FIELD_PRODUCT_UNIT_CODE],
            $shippingLineItem->getProductUnitCode()
        );
        $this->assertEquals(
            $shippingLineItemParams[ShippingLineItem::FIELD_QUANTITY],
            $shippingLineItem->getQuantity()
        );
        $this->assertEquals(
            $shippingLineItemParams[ShippingLineItem::FIELD_PRODUCT_HOLDER],
            $shippingLineItem->getProductHolder()
        );
        $this->assertEquals($shippingLineItemParams[ShippingLineItem::FIELD_PRODUCT], $shippingLineItem->getProduct());
        $this->assertEquals(
            $shippingLineItemParams[ShippingLineItem::FIELD_PRODUCT_SKU],
            $shippingLineItem->getProductSku()
        );
        $this->assertEquals(
            $shippingLineItemParams[ShippingLineItem::FIELD_DIMENSIONS],
            $shippingLineItem->getDimensions()
        );
        $this->assertEquals($shippingLineItemParams[ShippingLineItem::FIELD_WEIGHT], $shippingLineItem->getWeight());
        $this->assertEquals(
            $shippingLineItemParams[ShippingLineItem::FIELD_ENTITY_IDENTIFIER],
            $shippingLineItem->getEntityIdentifier()
        );
    }
}
