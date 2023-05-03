<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Converter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Converter\BasicOrderShippingLineItemConverter;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Basic\Factory\BasicShippingLineItemBuilderFactory;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\Factory\DoctrineShippingLineItemCollectionFactory;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Component\Testing\ReflectionUtil;

class BasicOrderShippingLineItemConverterTest extends \PHPUnit\Framework\TestCase
{
    private BasicOrderShippingLineItemConverter $orderShippingLineItemConverter;

    protected function setUp(): void
    {
        $this->orderShippingLineItemConverter = new BasicOrderShippingLineItemConverter(
            new DoctrineShippingLineItemCollectionFactory(),
            new BasicShippingLineItemBuilderFactory()
        );
    }

    private function getProduct(int $id): Product
    {
        $product = new Product();
        ReflectionUtil::setId($product, $id);

        return $product;
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
        ?Product $product
    ): OrderLineItem {
        $lineItem = new OrderLineItem();
        $lineItem->setQuantity($quantity);
        $lineItem->setProductUnit($productUnit);
        $lineItem->setPrice($price);
        $lineItem->setProduct($product);

        return $lineItem;
    }

    private function createExpected(OrderLineItem $lineItem): array
    {
        return [
            'quantity' => $lineItem->getQuantity(),
            'product_holder' => $lineItem,
            'product_unit' => $lineItem->getProductUnit(),
            'product_unit_code' => $lineItem->getProductUnit()->getCode(),
            'entity_id' => null
        ];
    }

    /**
     * @dataProvider lineItemsDataProvider
     */
    public function testConvertLineItems(array $lineItems, array $expectedShippingLineItems)
    {
        $this->assertEquals(
            new DoctrineShippingLineItemCollection($expectedShippingLineItems),
            $this->orderShippingLineItemConverter->convertLineItems(new ArrayCollection($lineItems))
        );
    }

    public function lineItemsDataProvider(): array
    {
        $product = $this->getProduct(123);
        $unit1 = $this->getProductUnit('item');
        $unit2 = $this->getProductUnit('set');

        $lineItems = [
            $this->getLineItem(12.0, $unit1, $this->getPrice(10.5), null),
            $this->getLineItem(5.0, $unit2, null, $product),
            $this->getLineItem(7.0, $unit2, $this->getPrice(99.9), $product)
        ];

        return [
            'all line items have required properties' => [
                'lineItems' => $lineItems,
                'expectedShippingLineItems' => [
                    new ShippingLineItem(array_merge($this->createExpected($lineItems[0]), [
                        'price' => $lineItems[0]->getPrice(),
                    ])),
                    new ShippingLineItem(array_merge($this->createExpected($lineItems[1]), [
                        'product' => $product,
                    ])),
                    new ShippingLineItem(array_merge($this->createExpected($lineItems[2]), [
                        'product' => $product,
                        'price' => $lineItems[2]->getPrice(),
                    ]))
                ],
            ],
            'some line items have no product unit' => [
                'lineItems' => [
                    $this->getLineItem(12.0, $unit1, $this->getPrice(10.5), null),
                    $this->getLineItem(1.0, null, $this->getPrice(1.3), null),
                ],
                'expectedShippingLineItems' => [],
            ],
        ];
    }
}
