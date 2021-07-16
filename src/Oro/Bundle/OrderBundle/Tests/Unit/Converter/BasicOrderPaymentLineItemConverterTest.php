<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Converter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Converter\BasicOrderPaymentLineItemConverter;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PaymentBundle\Context\LineItem\Builder\Basic\Factory\BasicPaymentLineItemBuilderFactory;
use Oro\Bundle\PaymentBundle\Context\LineItem\Builder\Factory\PaymentLineItemBuilderFactoryInterface;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\Doctrine\DoctrinePaymentLineItemCollection;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\Doctrine\Factory\DoctrinePaymentLineItemCollectionFactory;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

class BasicOrderPaymentLineItemConverterTest extends \PHPUnit\Framework\TestCase
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

    protected function setUp(): void
    {
        $this->paymentLineItemBuilderFactory = new BasicPaymentLineItemBuilderFactory();
        $this->collectionFactory = new DoctrinePaymentLineItemCollectionFactory();
        $this->orderPaymentLineItemConverter = new BasicOrderPaymentLineItemConverter(
            $this->collectionFactory,
            $this->paymentLineItemBuilderFactory
        );
    }

    /**
     * @dataProvider convertLineItemsDataProvider
     */
    public function testConvertLineItems(Collection $orderCollection, array $expectedLineItems)
    {
        $expectedLineItemCollection = new DoctrinePaymentLineItemCollection($expectedLineItems);

        $paymentLineItemCollection = $this->orderPaymentLineItemConverter->convertLineItems($orderCollection);

        $this->assertEquals($expectedLineItemCollection, $paymentLineItemCollection);
    }

    /**
     * @return array
     */
    public function convertLineItemsDataProvider()
    {
        $productUnitCode = 'someCode';
        $productUnitMock = $this->mockProductUnit($productUnitCode);
        $product = $this->createMock(Product::class);
        $price = $this->createMock(Price::class);

        $normalOrderCollection = new ArrayCollection([
            $this->createOrderLineItem(12, $productUnitMock),
            $this->createOrderLineItem(5, $productUnitMock),
            $this->createOrderLineItem(1, $productUnitMock),
            $this->createOrderLineItem(3, $productUnitMock),
        ]);

        $normalExpectedLineItems = [];
        foreach ($normalOrderCollection as $orderLineItem) {
            $normalExpectedLineItems[] = new PaymentLineItem([
                PaymentLineItem::FIELD_QUANTITY => $orderLineItem->getQuantity(),
                PaymentLineItem::FIELD_PRODUCT_HOLDER => $orderLineItem,
                PaymentLineItem::FIELD_PRODUCT_UNIT => $orderLineItem->getProductUnit(),
                PaymentLineItem::FIELD_PRODUCT_UNIT_CODE => $productUnitCode,
                PaymentLineItem::FIELD_ENTITY_IDENTIFIER => null,
            ]);
        }

        $data['required fields only'] = [
            'orderCollection' => $normalOrderCollection,
            'expectedLineItems' => $normalExpectedLineItems,
        ];

        $withPriceOrderCollection = new ArrayCollection([
            $this->createOrderLineItem(12, $productUnitMock)->setPrice($price)->setProduct($product),
            $this->createOrderLineItem(5, $productUnitMock)->setPrice($price)->setProduct($product),
            $this->createOrderLineItem(1, $productUnitMock)->setPrice($price)->setProduct($product),
            $this->createOrderLineItem(3, $productUnitMock)->setPrice($price)->setProduct($product),
        ]);

        $withPriceExpectedLineItems = [];
        foreach ($withPriceOrderCollection as $orderLineItem) {
            $withPriceExpectedLineItems[] = new PaymentLineItem([
                PaymentLineItem::FIELD_QUANTITY => $orderLineItem->getQuantity(),
                PaymentLineItem::FIELD_PRODUCT_HOLDER => $orderLineItem,
                PaymentLineItem::FIELD_PRODUCT_UNIT => $orderLineItem->getProductUnit(),
                PaymentLineItem::FIELD_PRODUCT_UNIT_CODE => $productUnitCode,
                PaymentLineItem::FIELD_PRICE => $orderLineItem->getPrice(),
                PaymentLineItem::FIELD_PRODUCT => $orderLineItem->getProduct(),
                PaymentLineItem::FIELD_ENTITY_IDENTIFIER => null,
            ]);
        }

        $data['with optional price and product'] = [
            'orderCollection' => $withPriceOrderCollection,
            'expectedLineItems' => $withPriceExpectedLineItems,
        ];

        return $data;
    }

    public function testWithoutRequiredFieldsOnOrderLineItems()
    {
        $productUnitMock = $this->createMock(ProductUnit::class);
        $productUnitMock
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

    /**
     * @param int                                                  $quantity
     * @param ProductUnit|\PHPUnit\Framework\MockObject\MockObject $productUnitMock
     *
     * @return OrderLineItem
     */
    private function createOrderLineItem($quantity, $productUnitMock)
    {
        return (new OrderLineItem())->setQuantity($quantity)->setProductUnit($productUnitMock);
    }

    /**
     * @param string $productUnitCode
     *
     * @return ProductUnit|\PHPUnit\Framework\MockObject\MockObject
     */
    private function mockProductUnit($productUnitCode)
    {
        $productUnitMock = $this->createMock(ProductUnit::class);
        $productUnitMock
            ->expects($this->atLeastOnce())
            ->method('getCode')
            ->willReturn($productUnitCode);

        return $productUnitMock;
    }
}
