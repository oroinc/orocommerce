<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Modifier;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Basic\Factory\BasicShippingLineItemBuilderFactory;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\Factory\DoctrineShippingLineItemCollectionFactory;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Entity\Repository\ProductShippingOptionsRepository;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Oro\Bundle\ShippingBundle\Modifier\AddProductOptionsShippingLineItemCollectionModifier;
use PHPUnit\Framework\TestCase;

class AddProductOptionsShippingLineItemCollectionModifierTest extends TestCase
{
    /**
     * @var DoctrineShippingLineItemCollectionFactory
     */
    private $collectionFactory;

    /**
     * @var ProductShippingOptionsRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $optionsRepository;

    /**
     * @var BasicShippingLineItemBuilderFactory
     */
    private $lineItemBuilderFactory;

    /**
     * @var AddProductOptionsShippingLineItemCollectionModifier
     */
    private $modifier;

    protected function setUp()
    {
        $this->collectionFactory = new DoctrineShippingLineItemCollectionFactory();
        $this->optionsRepository = $this->createMock(ProductShippingOptionsRepository::class);
        $this->lineItemBuilderFactory = new BasicShippingLineItemBuilderFactory();

        $this->modifier = new AddProductOptionsShippingLineItemCollectionModifier(
            $this->collectionFactory,
            $this->optionsRepository,
            $this->lineItemBuilderFactory
        );
    }

    public function testModify()
    {
        $items = [
            [
                ShippingLineItem::FIELD_PRODUCT => $this->createProduct(23),
                ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => 'item',
                ShippingLineItem::FIELD_PRODUCT_UNIT => $this->createProductUnit('item'),
                ShippingLineItem::FIELD_PRODUCT_HOLDER => $this->createMock(ProductHolderInterface::class),
                ShippingLineItem::FIELD_QUANTITY => 4
            ],
            [
                ShippingLineItem::FIELD_ENTITY_IDENTIFIER => '2',
                ShippingLineItem::FIELD_QUANTITY => 8
            ],
            [
                ShippingLineItem::FIELD_PRODUCT => $this->createProduct(12),
                ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => 'set',
                ShippingLineItem::FIELD_PRODUCT_UNIT => $this->createProductUnit('set'),
                ShippingLineItem::FIELD_PRODUCT_HOLDER => $this->createMock(ProductHolderInterface::class),
                ShippingLineItem::FIELD_QUANTITY => 8
            ],
            [
                ShippingLineItem::FIELD_PRODUCT => $this->createProduct(5),
                ShippingLineItem::FIELD_PRODUCT_SKU => 'sku',
                ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => 'each',
                ShippingLineItem::FIELD_PRODUCT_UNIT => $this->createProductUnit('each'),
                ShippingLineItem::FIELD_PRODUCT_HOLDER => $this->createMock(ProductHolderInterface::class),
                ShippingLineItem::FIELD_PRICE => Price::create(135, 'USD'),
                ShippingLineItem::FIELD_QUANTITY => 5,
            ],
        ];

        $lineItemCollection = new DoctrineShippingLineItemCollection([
            new ShippingLineItem($items[0]),
            new ShippingLineItem($items[1]),
            new ShippingLineItem($items[2]),
            new ShippingLineItem($items[3]),
        ]);

        $productOptions = [
            $this->createShippingOption(23, 'item', Dimensions::create(1, 2, 3), Weight::create(10)),
            $this->createShippingOption(12, 'set', Dimensions::create(13, 15, 20), null),
            $this->createShippingOption(5, 'each', null, Weight::create(6)),
        ];

        $this->optionsRepository
            ->expects(static::once())
            ->method('findByProductsAndProductUnits')
            ->willReturn($productOptions);

        static::assertEquals(
            new DoctrineShippingLineItemCollection([
                new ShippingLineItem(array_merge(
                    $items[0],
                    [
                        ShippingLineItem::FIELD_ENTITY_IDENTIFIER => null,
                        ShippingLineItem::FIELD_DIMENSIONS => Dimensions::create(1, 2, 3),
                        ShippingLineItem::FIELD_WEIGHT => Weight::create(10),
                    ]
                )),
                new ShippingLineItem($items[1]),
                new ShippingLineItem(array_merge(
                    $items[2],
                    [
                        ShippingLineItem::FIELD_ENTITY_IDENTIFIER => null,
                        ShippingLineItem::FIELD_DIMENSIONS => Dimensions::create(13, 15, 20),
                    ]
                )),
                new ShippingLineItem(array_merge(
                    $items[3],
                    [
                        ShippingLineItem::FIELD_ENTITY_IDENTIFIER => null,
                        ShippingLineItem::FIELD_WEIGHT => Weight::create(6),
                    ]
                )),
            ]),
            $this->modifier->modify($lineItemCollection)
        );
    }

    /**
     * @param int             $productId
     * @param string          $unitCode
     * @param Dimensions|null $dimensions
     * @param Weight|null     $weight
     *
     * @return ProductShippingOptions
     */
    private function createShippingOption(
        int $productId,
        string $unitCode,
        Dimensions $dimensions = null,
        Weight $weight = null
    ): ProductShippingOptions {
        $option = new ProductShippingOptions();

        $option
            ->setProduct($this->createProduct($productId))
            ->setProductUnit($this->createProductUnit($unitCode))
            ->setDimensions($dimensions)
            ->setWeight($weight);

        return $option;
    }

    /**
     * @param string $code
     *
     * @return ProductUnit
     */
    private function createProductUnit(string $code): ProductUnit
    {
        return (new ProductUnit())->setCode($code);
    }

    /**
     * @param int $id
     *
     * @return Product|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createProduct(int $id)
    {
        $product = $this->createMock(Product::class);
        $product
            ->expects(static::any())
            ->method('getId')
            ->willReturn($id);

        return $product;
    }
}
