<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Converter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Converter\BasicOrderShippingLineItemConverter;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Basic\Factory\BasicShippingLineItemBuilderFactory;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Factory\ShippingLineItemBuilderFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\Factory\DoctrineShippingLineItemCollectionFactory;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;

class BasicOrderShippingLineItemConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineShippingLineItemCollectionFactory
     */
    private $collectionFactory;

    /**
     * @var BasicOrderShippingLineItemConverter
     */
    private $orderShippingLineItemConverter;

    /**
     * @var ShippingLineItemBuilderFactoryInterface|
     */
    private $shippingLineItemBuilderFactory;

    /**
     * @var ProductUnit|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productUnitMock;

    /**
     * @var Price|\PHPUnit_Framework_MockObject_MockObject
     */
    private $priceMock;

    public function setUp()
    {
        $this->shippingLineItemBuilderFactory = new BasicShippingLineItemBuilderFactory();
        $this->collectionFactory = new DoctrineShippingLineItemCollectionFactory();
        $this->orderShippingLineItemConverter = new BasicOrderShippingLineItemConverter(
            $this->collectionFactory,
            $this->shippingLineItemBuilderFactory
        );
        $this->productUnitMock = $this->getMockBuilder(ProductUnit::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->priceMock = $this->getMockBuilder(Price::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testConvertLineItems()
    {
        $productUnitCode = 'someCode';

        $orderCollection = new ArrayCollection([
            (new OrderLineItem())->setQuantity(12)->setProductUnit($this->productUnitMock)->setPrice($this->priceMock),
            (new OrderLineItem())->setQuantity(5)->setProductUnit($this->productUnitMock)->setPrice($this->priceMock),
            (new OrderLineItem())->setQuantity(1)->setProductUnit($this->productUnitMock)->setPrice($this->priceMock),
            (new OrderLineItem())->setQuantity(3)->setProductUnit($this->productUnitMock)->setPrice($this->priceMock),
            (new OrderLineItem())->setQuantity(50)->setProductUnit($this->productUnitMock)->setPrice($this->priceMock),
        ]);

        $this->productUnitMock
            ->expects($this->exactly($orderCollection->count()))
            ->method('getCode')
            ->willReturn($productUnitCode);

        $expectedPaymentLineItems = [];
        foreach ($orderCollection as $orderLineItem) {
            $expectedPaymentLineItems[] = new ShippingLineItem([
                ShippingLineItem::FIELD_QUANTITY => $orderLineItem->getQuantity(),
                ShippingLineItem::FIELD_PRODUCT_HOLDER => $orderLineItem,
                ShippingLineItem::FIELD_PRODUCT_UNIT => $orderLineItem->getProductUnit(),
                ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => $productUnitCode,
                ShippingLineItem::FIELD_PRICE => $orderLineItem->getPrice(),
                ShippingLineItem::FIELD_ENTITY_IDENTIFIER => null,
            ]);
        }
        $expectedLineItemCollection = new DoctrineShippingLineItemCollection($expectedPaymentLineItems);

        $paymentLineItemCollection = $this->orderShippingLineItemConverter->convertLineItems($orderCollection);

        $this->assertEquals($expectedLineItemCollection, $paymentLineItemCollection);
    }

    public function testWithoutRequiredFieldsOnOrderLineItems()
    {
        $this->productUnitMock
            ->expects($this->never())
            ->method('getCode');

        $orderCollection = new ArrayCollection([
            (new OrderLineItem())->setQuantity(12),
            (new OrderLineItem())->setQuantity(5),
            (new OrderLineItem())->setQuantity(1),
            (new OrderLineItem())->setQuantity(3),
            (new OrderLineItem())->setQuantity(50),
        ]);

        $expectedLineItemCollection = new DoctrineShippingLineItemCollection([]);

        $shippingLineItemCollection = $this->orderShippingLineItemConverter->convertLineItems($orderCollection);

        $this->assertEquals($expectedLineItemCollection, $shippingLineItemCollection);
    }
}
