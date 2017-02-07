<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;

abstract class AbstractShippingLineItemTest extends \PHPUnit_Framework_TestCase
{
    const TEST_CODE = 'someCode';
    const TEST_QUANTITY = 15;
    const TEST_SKU = 'someSku';
    const TEST_ID = 'someId';

    /**
     * @var Price|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceMock;

    /**
     * @var ProductUnit|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productUnitMock;

    /**
     * @var ProductHolderInterface|\PHPUnit_Framework_MockObject_MockObject
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

    public function setUp()
    {
        $this->priceMock = $this->getMockBuilder(Price::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productUnitMock = $this->getMockBuilder(ProductUnit::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productHolderMock = $this->createMock(ProductHolderInterface::class);

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

    /**
     * @return array
     */
    protected function getShippingLineItemParams()
    {
        return [
            ShippingLineItem::FIELD_PRICE => $this->priceMock,
            ShippingLineItem::FIELD_PRODUCT_UNIT => $this->productUnitMock,
            ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => self::TEST_CODE,
            ShippingLineItem::FIELD_QUANTITY => self::TEST_QUANTITY,
            ShippingLineItem::FIELD_PRODUCT_HOLDER => $this->productHolderMock,
            ShippingLineItem::FIELD_PRODUCT => $this->productMock,
            ShippingLineItem::FIELD_PRODUCT_SKU => self::TEST_SKU,
            ShippingLineItem::FIELD_DIMENSIONS => $this->dimensionsMock,
            ShippingLineItem::FIELD_WEIGHT => $this->weightMock,
            ShippingLineItem::FIELD_ENTITY_IDENTIFIER => self::TEST_ID,
        ];
    }
}
