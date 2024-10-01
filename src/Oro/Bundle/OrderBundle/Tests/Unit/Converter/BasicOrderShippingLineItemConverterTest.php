<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Converter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Converter\BasicOrderShippingLineItemConverter;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ShippingBundle\Context\LineItem\Factory\ShippingLineItemFromProductLineItemFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BasicOrderShippingLineItemConverterTest extends TestCase
{
    private ShippingLineItemFromProductLineItemFactoryInterface|MockObject $shippingLineItemFactory;

    private BasicOrderShippingLineItemConverter $orderShippingLineItemConverter;

    #[\Override]
    protected function setUp(): void
    {
        $this->shippingLineItemFactory = $this->createMock(ShippingLineItemFromProductLineItemFactoryInterface::class);

        $this->orderShippingLineItemConverter = new BasicOrderShippingLineItemConverter(
            $this->shippingLineItemFactory
        );
    }

    private function getProduct(int $id): Product
    {
        return (new ProductStub())
            ->setId($id);
    }

    private function getProductUnit(string $code): ProductUnit
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode($code);

        return $productUnit;
    }

    private function getPrice(float $value): Price
    {
        $price = new Price();
        $price->setValue($value);

        return $price;
    }

    private function getLineItem(
        float $quantity,
        ?ProductUnit $productUnit,
        ?Price $price,
        ?Product $product,
        array $kitItemLineItems = []
    ): OrderLineItem {
        $lineItem = new OrderLineItem();
        $lineItem->setQuantity($quantity);
        $lineItem->setProductUnit($productUnit);
        $lineItem->setPrice($price);
        $lineItem->setProduct($product);
        foreach ($kitItemLineItems as $kitItemLineItem) {
            $lineItem->addKitItemLineItem($kitItemLineItem);
        }

        return $lineItem;
    }

    private function getKitItemLineItem(
        float $quantity,
        ?ProductUnit $productUnit,
        ?Price $price,
        ?Product $product
    ): OrderProductKitItemLineItem {
        return (new OrderProductKitItemLineItem())
            ->setProduct($product)
            ->setProductUnit($productUnit)
            ->setQuantity($quantity)
            ->setPrice($price)
            ->setSortOrder(1);
    }

    /**
     * @dataProvider lineItemsDataProvider
     */
    public function testConvertLineItems(
        array $lineItems,
        array $expectedLineItemsToConvert,
        array $expectedShippingLineItems
    ): void {
        $this->shippingLineItemFactory->expects(self::once())
            ->method('createCollection')
            ->with($expectedLineItemsToConvert)
            ->willReturn(new ArrayCollection($expectedShippingLineItems));

        self::assertEquals(
            new ArrayCollection($expectedShippingLineItems),
            $this->orderShippingLineItemConverter->convertLineItems(new ArrayCollection($lineItems))
        );
    }

    public function lineItemsDataProvider(): array
    {
        $product = $this->getProduct(123);
        $unit1 = $this->getProductUnit('item');
        $unit2 = $this->getProductUnit('set');

        $kitItemLineItem = $this->getKitItemLineItem(
            1,
            $unit1,
            $this->getPrice(13),
            $this->getProduct(1)
        );

        $lineItems = [
            $this->getLineItem(12.0, $unit1, $this->getPrice(10.5), null),
            $this->getLineItem(5.0, $unit2, null, $product),
            $this->getLineItem(7.0, $unit2, $this->getPrice(99.9), $product, [$kitItemLineItem])
        ];

        return [
            'all line items have required properties' => [
                'lineItems' => $lineItems,
                'expectedLineItemsToConvert' => $lineItems,
                'expectedShippingLineItems' => [
                    (new ShippingLineItem(
                        $lineItems[0]->getProductUnit(),
                        $lineItems[0]->getQuantity(),
                        $lineItems[0]->getProductHolder()
                    ))
                        ->setProduct(null)
                        ->setPrice($lineItems[0]->getPrice()),
                    (new ShippingLineItem(
                        $lineItems[1]->getProductUnit(),
                        $lineItems[1]->getQuantity(),
                        $lineItems[1]->getProductHolder()
                    ))
                        ->setProduct($product)
                        ->setPrice(null),
                    (new ShippingLineItem(
                        $lineItems[2]->getProductUnit(),
                        $lineItems[2]->getQuantity(),
                        $lineItems[2]->getProductHolder()
                    ))
                        ->setProduct($product)
                        ->setPrice($lineItems[2]->getPrice())
                        ->setKitItemLineItems(new ArrayCollection([$kitItemLineItem])),
                ],
            ],
            'some line items have no product unit' => [
                'lineItems' => [
                    $this->getLineItem(12.0, $unit1, $this->getPrice(10.5), null),
                    $this->getLineItem(1.0, null, $this->getPrice(1.3), null),
                ],
                'expectedLineItemsToConvert' => [],
                'expectedShippingLineItems' => [],
            ],
        ];
    }
}
