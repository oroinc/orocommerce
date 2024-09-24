<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Converter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Converter\BasicOrderPaymentLineItemConverter;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\PaymentBundle\Context\LineItem\Factory\PaymentLineItemFromProductLineItemFactoryInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BasicOrderPaymentLineItemConverterTest extends TestCase
{
    private PaymentLineItemFromProductLineItemFactoryInterface|MockObject $paymentLineItemFactory;

    private BasicOrderPaymentLineItemConverter $orderPaymentLineItemConverter;

    #[\Override]
    protected function setUp(): void
    {
        $this->paymentLineItemFactory = $this->createMock(PaymentLineItemFromProductLineItemFactoryInterface::class);

        $this->orderPaymentLineItemConverter = new BasicOrderPaymentLineItemConverter(
            $this->paymentLineItemFactory
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
    public function testConvertLineItems(Collection $orderCollection, array $expectedPaymentLineItems): void
    {
        $this->paymentLineItemFactory->expects(self::once())
            ->method('createCollection')
            ->with($orderCollection->toArray())
            ->willReturn(new ArrayCollection($expectedPaymentLineItems));

        self::assertEquals(
            new ArrayCollection($expectedPaymentLineItems),
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
            $this->getOrderLineItem(3.0, $productUnit, [$kitItemLineItem]),
        ]);

        $normalExpectedLineItems = [];
        foreach ($normalOrderCollection as $orderLineItem) {
            $normalExpectedLineItems[] = new PaymentLineItem(
                $orderLineItem->getProductUnit(),
                $orderLineItem->getQuantity(),
                $orderLineItem
            );
        }

        $data['required fields only'] = [
            'orderCollection' => $normalOrderCollection,
            'expectedPaymentLineItems' => $normalExpectedLineItems,
        ];

        $withPriceOrderCollection = new ArrayCollection([
            $this->getOrderLineItem(12.0, $productUnit)->setPrice($price)->setProduct($product),
            $this->getOrderLineItem(5.0, $productUnit)->setPrice($price)->setProduct($product),
            $this->getOrderLineItem(1.0, $productUnit)->setPrice($price)->setProduct($product),
            $this->getOrderLineItem(3.0, $productUnit)->setPrice($price)->setProduct($product),
        ]);

        $withPriceExpectedLineItems = [];
        foreach ($withPriceOrderCollection as $orderLineItem) {
            $withPriceExpectedLineItems[] = (new PaymentLineItem(
                $orderLineItem->getProductUnit(),
                $orderLineItem->getQuantity(),
                $orderLineItem
            ))
                ->setProduct($orderLineItem->getProduct())
                ->setPrice($orderLineItem->getPrice());
        }

        $data['with optional price and product'] = [
            'orderCollection' => $withPriceOrderCollection,
            'expectedPaymentLineItems' => $withPriceExpectedLineItems,
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

        $expectedPaymentLineItems = new ArrayCollection([]);
        $this->paymentLineItemFactory->expects(self::once())
            ->method('createCollection')
            ->with([])
            ->willReturn($expectedPaymentLineItems);

        self::assertSame(
            $expectedPaymentLineItems,
            $this->orderPaymentLineItemConverter->convertLineItems($orderCollection)
        );
    }
}
