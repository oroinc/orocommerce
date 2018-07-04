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
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Entity\Repository\ProductShippingOptionsRepository;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Oro\Bundle\ShippingBundle\Tests\Unit\Context\AbstractShippingLineItemTest;

class ShippingOptionsLineItemCollectionFactoryDecoratorTest extends AbstractShippingLineItemTest
{
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
    public function setUp()
    {
        parent::setUp();

        $this->decoratedFactory = $this->createMock(ShippingLineItemCollectionFactoryInterface::class);

        $this->repository = $this->createMock(ProductShippingOptionsRepository::class);

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->doctrineHelper
            ->method('getEntityRepository')
            ->with(ProductShippingOptions::class)
            ->willReturn($this->repository);

        $this->builderByLineItemFactory = $this->createMock(LineItemBuilderByLineItemFactoryInterface::class);

        $this->decorator = new ShippingOptions\Factory\ShippingOptionsLineItemCollectionFactoryDecorator(
            $this->decoratedFactory,
            $this->doctrineHelper,
            $this->builderByLineItemFactory
        );
    }

    public function testCreateShippingLineItemCollection()
    {
        $lineItem1 = $this->createLineItemWithoutShippingOptions();
        $lineItem2 = $this->createLineItemWithoutShippingOptions();

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
            ->method('findByProductsAndUnits')
            ->willReturn([
                $this->createShippingOptionsMock($this->productMock, $this->weightMock, $this->dimensionsMock),
                $this->createShippingOptionsMock($this->productMock, $this->weightMock, $this->dimensionsMock),
            ]);

        $lineItemCollection = $this->createShippingLineItemCollectionMock();

        $newShippingLineItems = [
            $this->createLineItem(),
            $this->createLineItem(),
        ];

        $this->decoratedFactory
            ->method('createShippingLineItemCollection')
            ->with($newShippingLineItems)
            ->willReturn($lineItemCollection);

        static::assertSame($lineItemCollection, $this->decorator->createShippingLineItemCollection($shippingLineItems));
    }

    public function testCreateShippingLineItemCollectionEmpty()
    {
        $array = [];
        $collection = $this->createShippingLineItemCollectionMock();

        $this->repository
            ->method('findByProductsAndUnits')
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
     * @return ShippingLineItemInterface
     */
    private function createLineItem(): ShippingLineItemInterface
    {
        $parameters = $this->getShippingLineItemParams();

        return new ShippingLineItem($parameters);
    }

    /**
     * @return ShippingLineItemInterface
     */
    private function createLineItemWithoutShippingOptions(): ShippingLineItemInterface
    {
        $parameters = $this->getShippingLineItemParams();

        unset($parameters[ShippingLineItem::FIELD_DIMENSIONS], $parameters[ShippingLineItem::FIELD_WEIGHT]);

        return new ShippingLineItem($parameters);
    }

    /**
     * @param Product    $product
     * @param Weight     $weight
     * @param Dimensions $dimensions
     *
     * @return ProductShippingOptions|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createShippingOptionsMock(Product $product, Weight $weight, Dimensions $dimensions)
    {
        $options = $this->createMock(ProductShippingOptions::class);
        $options->method('getProduct')->willReturn($product);
        $options->method('getWeight')->willReturn($weight);
        $options->method('getDimensions')->willReturn($dimensions);

        return $options;
    }
}
