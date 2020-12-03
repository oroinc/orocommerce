<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context\LineItem\Collection\ShippingOptions\Factory;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Basic\Factory\BasicLineItemBuilderByLineItemFactory;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Basic\Factory\BasicShippingLineItemBuilderFactory;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Factory\LineItemBuilderByLineItemFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\ShippingLineItemBuilderInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Factory\ShippingLineItemCollectionFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingOptions;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Entity\Repository\ProductShippingOptionsRepository;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Oro\Bundle\ShippingBundle\Tests\Unit\Context\AbstractShippingLineItemTest;

class ShippingOptionsLineItemCollectionFactoryDecoratorTest extends AbstractShippingLineItemTest
{
    const TEST_PRODUCT_ID = 2002;

    /**
     * @var ShippingLineItemCollectionFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $decoratedFactory;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    /**
     * @var ProductShippingOptionsRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $repository;

    /**
     * @var LineItemBuilderByLineItemFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $builderByLineItemFactory;

    /**
     * @var ShippingOptions\Factory\ShippingOptionsLineItemCollectionFactoryDecorator
     */
    private $decorator;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->decoratedFactory = $this->createMock(ShippingLineItemCollectionFactoryInterface::class);

        $this->repository = $this->createMock(ProductShippingOptionsRepository::class);

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->doctrineHelper
            ->method('getEntityRepository')
            ->with(ProductShippingOptions::class)
            ->willReturn($this->repository);

        $this->doctrineHelper
            ->method('getEntityReference')
            ->willReturnCallback(
                static function (string $className, string $unitCode) {
                    $unit = new $className();
                    $unit->setCode($unitCode);

                    return $unit;
                }
            );

        $this->builderByLineItemFactory = $this->createMock(LineItemBuilderByLineItemFactoryInterface::class);

        $this->decorator = new ShippingOptions\Factory\ShippingOptionsLineItemCollectionFactoryDecorator(
            $this->decoratedFactory,
            $this->doctrineHelper,
            $this->builderByLineItemFactory
        );
    }

    public function testCreateShippingLineItemCollection()
    {
        $product2 = $this->createMock(Product::class);

        $lineItem1 = $this->createLineItemWithoutShippingOptions($this->productMock);
        $lineItem2 = $this->createLineItemWithoutShippingOptions($product2);

        $shippingLineItems = [
            $lineItem1,
            $lineItem2,
        ];

        $builder1 = $this->createBuilderFromLineItem($lineItem1);
        $builder2 = $this->createBuilderFromLineItem($lineItem2);

        $this->builderByLineItemFactory->expects(static::at(0))
            ->method('createBuilder')
            ->with($lineItem1)
            ->willReturn($builder1);

        $this->builderByLineItemFactory->expects(static::at(1))
            ->method('createBuilder')
            ->with($lineItem2)
            ->willReturn($builder2);

        $this->repository
            ->method('findIndexedByProductsAndUnits')
            ->willReturn(
                [
                    1001 => [
                        'dimensionsHeight' => 3.0,
                        'dimensionsLength' => 1.0,
                        'dimensionsWidth' => 2.0,
                        'dimensionsUnit' => 'in',
                        'weightUnit' => 'kilo',
                        'weightValue' => 42.0,
                    ],
                    2002 => [
                        'dimensionsHeight' => 13.0,
                        'dimensionsLength' => 11.0,
                        'dimensionsWidth' => 12.0,
                        'dimensionsUnit' => 'meter',
                        'weightUnit' => 'lbs',
                        'weightValue' => 142.0,
                    ]
                ]
            );

        $lineItemCollection = $this->createShippingLineItemCollectionMock();

        $this->decoratedFactory
            ->method('createShippingLineItemCollection')
            ->with(
                [
                    $this->createLineItem(
                        $this->productMock,
                        Dimensions::create(11, 12, 13, (new LengthUnit())->setCode('meter')),
                        Weight::create(142, (new WeightUnit())->setCode('lbs'))
                    ),
                    $this->createLineItemWithoutShippingOptions($product2),
                ]
            )
            ->willReturn($lineItemCollection);

        static::assertSame($lineItemCollection, $this->decorator->createShippingLineItemCollection($shippingLineItems));
    }

    public function testCreateShippingLineItemCollectionEmpty()
    {
        $array = [];
        $collection = $this->createShippingLineItemCollectionMock();

        $this->repository
            ->method('findIndexedByProductsAndUnits')
            ->willReturn($array);

        $this->builderByLineItemFactory
            ->expects(static::never())
            ->method('createBuilder');

        $this->decoratedFactory
            ->method('createShippingLineItemCollection')
            ->with($array)
            ->willReturn($collection);

        static::assertSame($collection, $this->decorator->createShippingLineItemCollection($array));
    }

    /**
     * @return ShippingLineItemCollectionInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createShippingLineItemCollectionMock()
    {
        return $this->createMock(ShippingLineItemCollectionInterface::class);
    }

    /**
     * @param ShippingLineItemInterface $lineItem
     *
     * @return ShippingLineItemBuilderInterface
     */
    private function createBuilderFromLineItem(ShippingLineItemInterface $lineItem): ShippingLineItemBuilderInterface
    {
        $factory = new BasicLineItemBuilderByLineItemFactory(
            new BasicShippingLineItemBuilderFactory()
        );

        return $factory->createBuilder($lineItem);
    }

    /**
     * @param Product $product
     * @param Dimensions $dimensions
     * @param Weight $weight
     * @return ShippingLineItemInterface
     */
    private function createLineItem(Product $product, Dimensions $dimensions, Weight $weight): ShippingLineItemInterface
    {
        return new ShippingLineItem(
            [
                ShippingLineItem::FIELD_PRICE => $this->priceMock,
                ShippingLineItem::FIELD_PRODUCT_UNIT => $this->productUnitMock,
                ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => self::TEST_UNIT_CODE,
                ShippingLineItem::FIELD_QUANTITY => self::TEST_QUANTITY,
                ShippingLineItem::FIELD_PRODUCT_HOLDER => $this->productHolderMock,
                ShippingLineItem::FIELD_PRODUCT => $product,
                ShippingLineItem::FIELD_PRODUCT_SKU => self::TEST_PRODUCT_SKU,
                ShippingLineItem::FIELD_DIMENSIONS => $dimensions,
                ShippingLineItem::FIELD_WEIGHT => $weight,
                ShippingLineItem::FIELD_ENTITY_IDENTIFIER => self::TEST_ENTITY_ID,
            ]
        );
    }

    /**
     * @param Product $product
     * @return ShippingLineItemInterface
     */
    private function createLineItemWithoutShippingOptions(Product $product): ShippingLineItemInterface
    {
        return new ShippingLineItem(
            [
                ShippingLineItem::FIELD_PRICE => $this->priceMock,
                ShippingLineItem::FIELD_PRODUCT_UNIT => $this->productUnitMock,
                ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => self::TEST_UNIT_CODE,
                ShippingLineItem::FIELD_QUANTITY => self::TEST_QUANTITY,
                ShippingLineItem::FIELD_PRODUCT_HOLDER => $this->productHolderMock,
                ShippingLineItem::FIELD_PRODUCT => $product,
                ShippingLineItem::FIELD_PRODUCT_SKU => self::TEST_PRODUCT_SKU,
                ShippingLineItem::FIELD_ENTITY_IDENTIFIER => self::TEST_ENTITY_ID,
            ]
        );
    }
}
