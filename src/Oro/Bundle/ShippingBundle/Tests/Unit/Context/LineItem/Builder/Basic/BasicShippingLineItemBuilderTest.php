<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context\LineItem\Builder\Basic;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Basic\BasicShippingLineItemBuilder;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;

class BasicShippingLineItemBuilderTest extends \PHPUnit_Framework_TestCase
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

    public function testFullBuild()
    {
        $unitCode = 'someCode';
        $quantity = 15;
        $productSku = 'someSku';
        $entityIdentifier = 'someId';

        $this->productHolderMock
            ->expects($this->once())
            ->method('getEntityIdentifier')
            ->willReturn($entityIdentifier);

        $builder = new BasicShippingLineItemBuilder(
            $this->priceMock,
            $this->productUnitMock,
            $unitCode,
            $quantity,
            $this->productHolderMock
        );

        $builder
            ->setProduct($this->productMock)
            ->setProductSku($productSku)
            ->setDimensions($this->dimensionsMock)
            ->setWeight($this->weightMock);

        $shippingLineItem = $builder->getResult();

        $expectedShippingLineItem = new ShippingLineItem([
            ShippingLineItem::FIELD_PRICE => $this->priceMock,
            ShippingLineItem::FIELD_PRODUCT_UNIT => $this->productUnitMock,
            ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => $unitCode,
            ShippingLineItem::FIELD_QUANTITY => $quantity,
            ShippingLineItem::FIELD_PRODUCT_HOLDER => $this->productHolderMock,
            ShippingLineItem::FIELD_PRODUCT => $this->productMock,
            ShippingLineItem::FIELD_PRODUCT_SKU => $productSku,
            ShippingLineItem::FIELD_DIMENSIONS => $this->dimensionsMock,
            ShippingLineItem::FIELD_WEIGHT => $this->weightMock,
            ShippingLineItem::FIELD_ENTITY_IDENTIFIER => $entityIdentifier
        ]);

        $this->assertEquals($expectedShippingLineItem, $shippingLineItem);
    }

    public function testOptionalBuild()
    {
        $unitCode = 'someCode';
        $quantity = 15;
        $entityIdentifier = 'someId';

        $this->productHolderMock
            ->expects($this->once())
            ->method('getEntityIdentifier')
            ->willReturn($entityIdentifier);

        $builder = new BasicShippingLineItemBuilder(
            $this->priceMock,
            $this->productUnitMock,
            $unitCode,
            $quantity,
            $this->productHolderMock
        );

        $shippingLineItem = $builder->getResult();

        $expectedShippingLineItem = new ShippingLineItem([
            ShippingLineItem::FIELD_PRICE => $this->priceMock,
            ShippingLineItem::FIELD_PRODUCT_UNIT => $this->productUnitMock,
            ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => $unitCode,
            ShippingLineItem::FIELD_QUANTITY => $quantity,
            ShippingLineItem::FIELD_PRODUCT_HOLDER => $this->productHolderMock,
            ShippingLineItem::FIELD_ENTITY_IDENTIFIER => $entityIdentifier
        ]);

        $this->assertEquals($expectedShippingLineItem, $shippingLineItem);
    }
}
