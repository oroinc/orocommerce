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
use Oro\Component\Testing\Unit\EntityTrait;

class BasicOrderShippingLineItemConverterTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var DoctrineShippingLineItemCollectionFactory
     */
    private $collectionFactory;

    /**
     * @var BasicOrderShippingLineItemConverter
     */
    private $orderShippingLineItemConverter;

    /**
     * @var BasicShippingLineItemBuilderFactory
     */
    private $shippingLineItemBuilderFactory;

    protected function setUp(): void
    {
        $this->shippingLineItemBuilderFactory = new BasicShippingLineItemBuilderFactory();
        $this->collectionFactory = new DoctrineShippingLineItemCollectionFactory();

        $this->orderShippingLineItemConverter = new BasicOrderShippingLineItemConverter(
            $this->collectionFactory,
            $this->shippingLineItemBuilderFactory
        );
    }

    /**
     * @return array
     */
    public function missingDependenciesDataProvider()
    {
        return [
            [
                'collectionFactory' => $this->collectionFactory,
                'shippingLineItemBuilderFactory' => null
            ],
            [
                'collectionFactory' => null,
                'shippingLineItemBuilderFactory' => $this->shippingLineItemBuilderFactory,
            ],
            [
                'collectionFactory' => null,
                'shippingLineItemBuilderFactory' => null
            ],
        ];
    }

    /**
     * @dataProvider missingDependenciesDataProvider
     */
    public function testConvertLineItemsWhenSomeDependencyMissing(
        DoctrineShippingLineItemCollectionFactory $collectionFactory = null,
        BasicShippingLineItemBuilderFactory $shippingLineItemBuilderFactory = null
    ) {
        $this->orderShippingLineItemConverter = new BasicOrderShippingLineItemConverter(
            $collectionFactory,
            $shippingLineItemBuilderFactory
        );

        $this->assertNull($this->orderShippingLineItemConverter->convertLineItems(new ArrayCollection([])));
    }

    /**
     * @dataProvider lineItemsDataProvider
     */
    public function testConvertLineItems(array $lineItems, array $expectetdShippingLineItems)
    {
        $this->assertEquals(
            new DoctrineShippingLineItemCollection($expectetdShippingLineItems),
            $this->orderShippingLineItemConverter->convertLineItems(new ArrayCollection($lineItems))
        );
    }

    /**
     * @return array
     */
    public function lineItemsDataProvider()
    {
        $product = $this->getEntity(Product::class, ['id' => 123]);
        $unit1 = $this->getEntity(ProductUnit::class, ['code' => 'item']);
        $unit2 = $this->getEntity(ProductUnit::class, ['code' => 'set']);

        $lineItems = [
            $this->getLineItem(
                ['quantity' => 12, 'productUnit' => $unit1, 'price' => $this->getPrice(10.5), 'product' => null]
            ),
            $this->getLineItem(
                ['quantity' => 5, 'productUnit' => $unit2, 'price' => null, 'product' => $product]
            ),
            $this->getLineItem(
                ['quantity' => 7, 'productUnit' => $unit2, 'price' => $this->getPrice(99.9), 'product' => $product]
            ),
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
                    $this->getLineItem(['quantity' => 12, 'productUnit' => $unit1, 'price' => $this->getPrice(10.5)]),
                    $this->getLineItem(['quantity' => 1, 'productUnit' => null, 'price' => $this->getPrice(1.3)]),
                ],
                'expectedShippingLineItems' => [],
            ],
        ];
    }

    /**
     * @param OrderLineItem $lineItem
     * @return array
     */
    private function createExpected(OrderLineItem $lineItem)
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
     * @param float $price
     * @return Price
     */
    private function getPrice($price)
    {
        return $this->getEntity(Price::class, ['value' => $price]);
    }

    /**
     * @param array $data
     * @return OrderLineItem
     */
    private function getLineItem(array $data)
    {
        return $this->getEntity(OrderLineItem::class, $data);
    }
}
