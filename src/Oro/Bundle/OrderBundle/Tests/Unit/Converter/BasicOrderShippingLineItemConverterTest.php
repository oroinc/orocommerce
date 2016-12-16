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

        $this->productUnitMock
            ->expects($this->exactly(5))
            ->method('getCode')
            ->willReturn($productUnitCode);

        $lineItemsData = [
            ['quantity' => 12, 'productUnit' => $this->productUnitMock, 'price' => $this->priceMock],
            ['quantity' => 5, 'productUnit' => $this->productUnitMock, 'price' => $this->priceMock ],
            ['quantity' => 1, 'productUnit' => $this->productUnitMock, 'price' => $this->priceMock],
            ['quantity' => 3, 'productUnit' => $this->productUnitMock, 'price' => $this->priceMock],
            ['quantity' => 50, 'productUnit' => $this->productUnitMock, 'price' => $this->priceMock],
        ];

        $orderLineItems = [];
        foreach ($lineItemsData as $lineItemData) {
            $orderLineItems[] = (new OrderLineItem())
                ->setQuantity($lineItemData['quantity'])
                ->setProductUnit($lineItemData['productUnit'])
                ->setPrice($this->priceMock);
        }
        $orderCollection = new ArrayCollection($orderLineItems);

        $shippingLineItemCollection = $this->orderShippingLineItemConverter->convertLineItems($orderCollection);

        $expectedShippingLineItems = [];

        foreach ($orderLineItems as $orderLineItem) {
            $expectedShippingLineItems[] = new ShippingLineItem([
                ShippingLineItem::FIELD_QUANTITY => $orderLineItem->getQuantity(),
                ShippingLineItem::FIELD_PRODUCT_HOLDER => $orderLineItem,
                ShippingLineItem::FIELD_PRODUCT_UNIT => $orderLineItem->getProductUnit(),
                ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => $productUnitCode,
                ShippingLineItem::FIELD_PRICE => $orderLineItem->getPrice(),
                ShippingLineItem::FIELD_ENTITY_IDENTIFIER => null,
            ]);
        }

        $expectedLineItemCollection = new DoctrineShippingLineItemCollection($expectedShippingLineItems);

        $this->assertEquals($expectedLineItemCollection, $shippingLineItemCollection);
    }
}
