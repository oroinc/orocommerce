<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context\LineItem\Collection\ShippingOptions\Factory;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
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

    private ShippingLineItemCollectionFactoryInterface|\PHPUnit\Framework\MockObject\MockObject $decoratedFactory;

    private ProductShippingOptionsRepository|\PHPUnit\Framework\MockObject\MockObject $repository;

    private LineItemBuilderByLineItemFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
        $builderByLineItemFactory;

    private ShippingOptions\Factory\ShippingOptionsLineItemCollectionFactoryDecorator $decorator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->decoratedFactory = $this->createMock(ShippingLineItemCollectionFactoryInterface::class);
        $this->repository = $this->createMock(ProductShippingOptionsRepository::class);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry
            ->expects(self::any())
            ->method('getRepository')
            ->with(ProductShippingOptions::class)
            ->willReturn($this->repository);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $managerRegistry
            ->expects(self::any())
            ->method('getManagerForClass')
            ->withConsecutive([WeightUnit::class], [LengthUnit::class])
            ->willReturn($entityManager);

        $entityManager
            ->expects(self::any())
            ->method('getReference')
            ->willReturnCallback(
                static function (string $className, ?string $unitCode) {
                    $unit = new $className();
                    $unit->setCode($unitCode);

                    return $unit;
                }
            );

        $this->builderByLineItemFactory = $this->createMock(LineItemBuilderByLineItemFactoryInterface::class);

        $this->decorator = new ShippingOptions\Factory\ShippingOptionsLineItemCollectionFactoryDecorator(
            $this->decoratedFactory,
            $managerRegistry,
            $this->builderByLineItemFactory
        );
    }

    public function testCreateShippingLineItemCollection(): void
    {
        $product2 = $this->createMock(Product::class);

        $lineItem1 = $this->createLineItemWithoutShippingOptions($this->productMock);
        $lineItem2 = $this->createLineItemWithoutShippingOptions($product2);

        $shippingLineItems = [$lineItem1, $lineItem2];

        $builder1 = $this->createBuilderFromLineItem($lineItem1);
        $builder2 = $this->createBuilderFromLineItem($lineItem2);

        $this->builderByLineItemFactory->expects(self::exactly(2))
            ->method('createBuilder')
            ->willReturnMap([[$lineItem1, $builder1], [$lineItem2, $builder2]]);

        $this->repository
            ->expects(self::once())
            ->method('findIndexedByProductsAndUnits')
            ->willReturn(
                [
                    1001 => [
                        'someCode' => [
                            'dimensionsHeight' => 3.0,
                            'dimensionsLength' => 1.0,
                            'dimensionsWidth' => 2.0,
                            'dimensionsUnit' => 'in',
                            'weightUnit' => 'kilo',
                            'weightValue' => 42.0,
                        ]
                    ],
                    2002 => [
                        'someCode' => [
                            'dimensionsHeight' => 13.0,
                            'dimensionsLength' => 11.0,
                            'dimensionsWidth' => 12.0,
                            'dimensionsUnit' => 'meter',
                            'weightUnit' => 'lbs',
                            'weightValue' => 142.0,
                        ]
                    ]
                ]
            );

        $lineItemCollection = $this->createMock(ShippingLineItemCollectionInterface::class);

        $this->decoratedFactory
            ->expects(self::once())
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

        self::assertSame($lineItemCollection, $this->decorator->createShippingLineItemCollection($shippingLineItems));
    }

    public function testCreateShippingLineItemCollectionWhenNoDimensionsUnit(): void
    {
        $lineItem1 = $this->createLineItemWithoutShippingOptions($this->productMock);
        $shippingLineItems = [$lineItem1];
        $builder1 = $this->createBuilderFromLineItem($lineItem1);

        $this->builderByLineItemFactory->expects(self::once())
            ->method('createBuilder')
            ->with($lineItem1)
            ->willReturn($builder1);

        $this->repository
            ->expects(self::once())
            ->method('findIndexedByProductsAndUnits')
            ->willReturn(
                [
                    2002 => [
                        'someCode' => [
                            'dimensionsHeight' => 13.0,
                            'dimensionsLength' => 11.0,
                            'dimensionsWidth' => 12.0,
                            'dimensionsUnit' => null,
                            'weightUnit' => 'lbs',
                            'weightValue' => 142.0,
                        ]
                    ],
                ]
            );

        $lineItemCollection = $this->createMock(ShippingLineItemCollectionInterface::class);

        $this->decoratedFactory
            ->expects(self::once())
            ->method('createShippingLineItemCollection')
            ->with(
                [
                    $this->createLineItem(
                        $this->productMock,
                        Dimensions::create(11, 12, 13, null),
                        Weight::create(142, (new WeightUnit())->setCode('lbs'))
                    ),
                ]
            )
            ->willReturn($lineItemCollection);

        self::assertSame($lineItemCollection, $this->decorator->createShippingLineItemCollection($shippingLineItems));
    }

    public function testCreateShippingLineItemCollectionWhenNoWeightUnit(): void
    {
        $lineItem1 = $this->createLineItemWithoutShippingOptions($this->productMock);
        $shippingLineItems = [$lineItem1];
        $builder1 = $this->createBuilderFromLineItem($lineItem1);

        $this->builderByLineItemFactory->expects(self::once())
            ->method('createBuilder')
            ->with($lineItem1)
            ->willReturn($builder1);

        $this->repository
            ->expects(self::once())
            ->method('findIndexedByProductsAndUnits')
            ->willReturn(
                [
                    2002 => [
                        'someCode' => [
                            'dimensionsHeight' => 13.0,
                            'dimensionsLength' => 11.0,
                            'dimensionsWidth' => 12.0,
                            'dimensionsUnit' => null,
                            'weightUnit' => null,
                            'weightValue' => 142.0,
                        ]
                    ],
                ]
            );

        $lineItemCollection = $this->createMock(ShippingLineItemCollectionInterface::class);

        $this->decoratedFactory
            ->expects(self::once())
            ->method('createShippingLineItemCollection')
            ->with(
                [
                    $this->createLineItem(
                        $this->productMock,
                        Dimensions::create(11, 12, 13, null),
                        Weight::create(142, null)
                    ),
                ]
            )
            ->willReturn($lineItemCollection);

        self::assertSame($lineItemCollection, $this->decorator->createShippingLineItemCollection($shippingLineItems));
    }

    public function testCreateShippingLineItemCollectionEmpty(): void
    {
        $collection = $this->createMock(ShippingLineItemCollectionInterface::class);

        $this->repository
            ->method('findIndexedByProductsAndUnits')
            ->willReturn([]);

        $this->builderByLineItemFactory
            ->expects(self::never())
            ->method('createBuilder');

        $this->decoratedFactory
            ->method('createShippingLineItemCollection')
            ->with([])
            ->willReturn($collection);

        self::assertSame($collection, $this->decorator->createShippingLineItemCollection([]));
    }

    private function createBuilderFromLineItem(ShippingLineItemInterface $lineItem): ShippingLineItemBuilderInterface
    {
        $factory = new BasicLineItemBuilderByLineItemFactory(new BasicShippingLineItemBuilderFactory());

        return $factory->createBuilder($lineItem);
    }

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
