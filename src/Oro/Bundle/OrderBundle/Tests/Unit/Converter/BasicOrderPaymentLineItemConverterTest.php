<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Converter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Converter\BasicOrderPaymentLineItemConverter;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\PaymentBundle\Context\LineItem\Builder\Basic\Factory\BasicPaymentLineItemBuilderFactory;
use Oro\Bundle\PaymentBundle\Context\LineItem\Builder\Factory\PaymentLineItemBuilderFactoryInterface;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\Doctrine\DoctrinePaymentLineItemCollection;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\Doctrine\Factory\DoctrinePaymentLineItemCollectionFactory;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;

class BasicOrderPaymentLineItemConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrinePaymentLineItemCollectionFactory
     */
    private $collectionFactory;

    /**
     * @var BasicOrderPaymentLineItemConverter
     */
    private $orderPaymentLineItemConverter;

    /**
     * @var PaymentLineItemBuilderFactoryInterface|
     */
    private $paymentLineItemBuilderFactory;

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
        $this->paymentLineItemBuilderFactory = new BasicPaymentLineItemBuilderFactory();
        $this->collectionFactory = new DoctrinePaymentLineItemCollectionFactory();
        $this->orderPaymentLineItemConverter = new BasicOrderPaymentLineItemConverter(
            $this->collectionFactory,
            $this->paymentLineItemBuilderFactory
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
            $expectedPaymentLineItems[] = new PaymentLineItem([
                PaymentLineItem::FIELD_QUANTITY => $orderLineItem->getQuantity(),
                PaymentLineItem::FIELD_PRODUCT_HOLDER => $orderLineItem,
                PaymentLineItem::FIELD_PRODUCT_UNIT => $orderLineItem->getProductUnit(),
                PaymentLineItem::FIELD_PRODUCT_UNIT_CODE => $productUnitCode,
                PaymentLineItem::FIELD_PRICE => $orderLineItem->getPrice(),
                PaymentLineItem::FIELD_ENTITY_IDENTIFIER => null,
            ]);
        }
        $expectedLineItemCollection = new DoctrinePaymentLineItemCollection($expectedPaymentLineItems);

        $paymentLineItemCollection = $this->orderPaymentLineItemConverter->convertLineItems($orderCollection);

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

        $expectedLineItemCollection = new DoctrinePaymentLineItemCollection([]);

        $paymentLineItemCollection = $this->orderPaymentLineItemConverter->convertLineItems($orderCollection);

        $this->assertEquals($expectedLineItemCollection, $paymentLineItemCollection);
    }
}
