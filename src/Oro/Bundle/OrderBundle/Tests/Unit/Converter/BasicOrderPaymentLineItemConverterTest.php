<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Converter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Converter\BasicOrderPaymentLineItemConverter;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PaymentBundle\Context\LineItem\Builder\Basic\Factory\BasicPaymentLineItemBuilderFactory;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\Doctrine\DoctrinePaymentLineItemCollection;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\Doctrine\Factory\DoctrinePaymentLineItemCollectionFactory;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

class BasicOrderPaymentLineItemConverterTest extends \PHPUnit\Framework\TestCase
{
    private BasicOrderPaymentLineItemConverter $orderPaymentLineItemConverter;

    protected function setUp(): void
    {
        $this->orderPaymentLineItemConverter = new BasicOrderPaymentLineItemConverter(
            new DoctrinePaymentLineItemCollectionFactory(),
            new BasicPaymentLineItemBuilderFactory()
        );
    }

    private function getProductUnit(string $code): ProductUnit
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode($code);

        return $productUnit;
    }

    private function getOrderLineItem(float $quantity, ?ProductUnit $productUnit): OrderLineItem
    {
        $lineItem = new OrderLineItem();
        $lineItem->setQuantity($quantity);
        $lineItem->setProductUnit($productUnit);

        return $lineItem;
    }

    /**
     * @dataProvider convertLineItemsDataProvider
     */
    public function testConvertLineItems(Collection $orderCollection, array $expectedLineItems)
    {
        $this->assertEquals(
            new DoctrinePaymentLineItemCollection($expectedLineItems),
            $this->orderPaymentLineItemConverter->convertLineItems($orderCollection)
        );
    }

    public function convertLineItemsDataProvider(): array
    {
        $productUnitCode = 'someCode';
        $productUnit = $this->getProductUnit($productUnitCode);
        $product = $this->createMock(Product::class);
        $price = $this->createMock(Price::class);

        $normalOrderCollection = new ArrayCollection([
            $this->getOrderLineItem(12.0, $productUnit),
            $this->getOrderLineItem(5.0, $productUnit),
            $this->getOrderLineItem(1.0, $productUnit),
            $this->getOrderLineItem(3.0, $productUnit),
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
            $this->getOrderLineItem(12.0, $productUnit)->setPrice($price)->setProduct($product),
            $this->getOrderLineItem(5.0, $productUnit)->setPrice($price)->setProduct($product),
            $this->getOrderLineItem(1.0, $productUnit)->setPrice($price)->setProduct($product),
            $this->getOrderLineItem(3.0, $productUnit)->setPrice($price)->setProduct($product),
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
        $productUnit = $this->createMock(ProductUnit::class);
        $productUnit->expects($this->never())
            ->method('getCode');

        $orderCollection = new ArrayCollection([
            $this->getOrderLineItem(12.0, null),
            $this->getOrderLineItem(5.0, null),
            $this->getOrderLineItem(1.0, null),
            $this->getOrderLineItem(3.0, null),
            $this->getOrderLineItem(50.0, null),
        ]);

        $this->assertEquals(
            new DoctrinePaymentLineItemCollection([]),
            $this->orderPaymentLineItemConverter->convertLineItems($orderCollection)
        );
    }
}
