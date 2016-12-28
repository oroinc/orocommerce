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

        $paymentLineItemCollection = $this->orderPaymentLineItemConverter->convertLineItems($orderCollection);

        $expectedPaymentLineItems = [];

        foreach ($orderLineItems as $orderLineItem) {
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

        $this->assertEquals($expectedLineItemCollection, $paymentLineItemCollection);
    }
}
