<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Converter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Converter\BasicOrderPaymentLineItemConverter;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\PaymentBundle\Context\LineItem\Builder\Basic\Factory\BasicPaymentLineItemBuilderFactory;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\Doctrine\DoctrinePaymentLineItemCollection;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\Doctrine\Factory\DoctrinePaymentLineItemCollectionFactory;
use Oro\Bundle\PaymentBundle\Context\PaymentKitItemLineItem;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\TestCase;

class BasicOrderPaymentLineItemConverterTest extends TestCase
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

    private function getProduct(int $id): Product
    {
        return (new ProductStub())
            ->setId($id);
    }

    private function getOrderLineItem(
        float $quantity,
        ?ProductUnit $productUnit,
        array $kitItemLineItems = []
    ): OrderLineItem {
        $lineItem = new OrderLineItem();
        $lineItem->setQuantity($quantity);
        $lineItem->setProductUnit($productUnit);
        foreach ($kitItemLineItems as $kitItemLineItem) {
            $lineItem->addKitItemLineItem($kitItemLineItem);
        }

        return $lineItem;
    }

    private function getKitItemLineItem(
        float $quantity,
        ?ProductUnit $productUnit,
        ?Price $price,
        ?Product $product,
    ): OrderProductKitItemLineItem {
        return (new OrderProductKitItemLineItem())
            ->setProduct($product)
            ->setProductUnit($productUnit)
            ->setQuantity($quantity)
            ->setPrice($price)
            ->setSortOrder(1);
    }

    /**
     * @dataProvider convertLineItemsDataProvider
     */
    public function testConvertLineItems(Collection $orderCollection, array $expectedLineItems): void
    {
        self::assertEquals(
            new DoctrinePaymentLineItemCollection($expectedLineItems),
            $this->orderPaymentLineItemConverter->convertLineItems($orderCollection)
        );
    }

    public function convertLineItemsDataProvider(): array
    {
        $productUnitCode = 'someCode';
        $productUnit = $this->getProductUnit($productUnitCode);
        $product = $this->getProduct(123);
        $price = Price::create(1, 'USD');

        $kitItemLineItem = $this->getKitItemLineItem(
            1,
            $productUnit,
            Price::create(13, 'USD'),
            $this->getProduct(1)
        );

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
                PaymentLineItem::FIELD_KIT_ITEM_LINE_ITEMS => new ArrayCollection(),
                PaymentLineItem::FIELD_CHECKSUM => '',
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
            $this->getOrderLineItem(3.0, $productUnit, [$kitItemLineItem])->setPrice($price)->setProduct($product),
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
                PaymentLineItem::FIELD_KIT_ITEM_LINE_ITEMS => new ArrayCollection(),
                PaymentLineItem::FIELD_CHECKSUM => '',
            ]);
        }

        $paymentKitItemLineItem = (new PaymentKitItemLineItem(
            $kitItemLineItem->getProductUnit(),
            $kitItemLineItem->getQuantity(),
            $kitItemLineItem->getProductHolder()
        ))
            ->setKitItem($kitItemLineItem->getKitItem())
            ->setProduct($kitItemLineItem->getProduct())
            ->setProductSku($kitItemLineItem->getProductSku())
            ->setSortOrder($kitItemLineItem->getSortOrder())
            ->setPrice($kitItemLineItem->getPrice());
        $withPriceExpectedLineItems[3]->setKitItemLineItems(new ArrayCollection([$paymentKitItemLineItem]));

        $data['with optional price and product'] = [
            'orderCollection' => $withPriceOrderCollection,
            'expectedLineItems' => $withPriceExpectedLineItems,
        ];

        return $data;
    }

    public function testWithoutRequiredFieldsOnOrderLineItems(): void
    {
        $productUnit = $this->createMock(ProductUnit::class);
        $productUnit->expects(self::never())
            ->method('getCode');

        $orderCollection = new ArrayCollection([
            $this->getOrderLineItem(12.0, null),
            $this->getOrderLineItem(5.0, null),
            $this->getOrderLineItem(1.0, null),
            $this->getOrderLineItem(3.0, null),
            $this->getOrderLineItem(50.0, $productUnit),
        ]);

        self::assertEquals(
            new DoctrinePaymentLineItemCollection([]),
            $this->orderPaymentLineItemConverter->convertLineItems($orderCollection)
        );
    }
}
