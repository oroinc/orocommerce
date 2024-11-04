<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

use Oro\Bundle\PricingBundle\Model\ProductLineItemPriceCriteriaFactory\ProductLineItemPriceCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaBuilder\ProductPriceCriteriaBuilderInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaBuilder\ProductPriceCriteriaBuilderRegistry;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaFactory;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\MockObject\MockObject;

class ProductPriceCriteriaFactoryTest extends \PHPUnit\Framework\TestCase
{
    private ProductPriceCriteriaBuilderRegistry|MockObject $productPriceCriteriaBuilderRegistry;

    private ProductLineItemPriceCriteriaFactoryInterface|MockObject $productLineItemPriceCriteriaFactory;

    private ProductPriceCriteriaFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->productPriceCriteriaBuilderRegistry = $this->createMock(ProductPriceCriteriaBuilderRegistry::class);
        $this->productLineItemPriceCriteriaFactory = $this->createMock(
            ProductLineItemPriceCriteriaFactoryInterface::class
        );

        $this->factory = new ProductPriceCriteriaFactory(
            $this->productPriceCriteriaBuilderRegistry,
            $this->productLineItemPriceCriteriaFactory
        );
    }

    public function testCreate(): void
    {
        $product = (new ProductStub())->setId(42);
        $productUnit = (new ProductUnit())->setCode('item');
        $quantity = 12.3456;
        $currency = 'USD';
        $builder = $this->createMock(ProductPriceCriteriaBuilderInterface::class);

        $this->productPriceCriteriaBuilderRegistry
            ->expects(self::once())
            ->method('getBuilderForProduct')
            ->with($product)
            ->willReturn($builder);

        $builder
            ->expects(self::once())
            ->method('setProduct')
            ->with($product)
            ->willReturnSelf();

        $builder
            ->expects(self::once())
            ->method('setProductUnit')
            ->with($productUnit)
            ->willReturnSelf();

        $builder
            ->expects(self::once())
            ->method('setQuantity')
            ->with($quantity)
            ->willReturnSelf();

        $builder
            ->expects(self::once())
            ->method('setCurrency')
            ->with($currency)
            ->willReturnSelf();

        $productPriceCriteria = $this->createMock(ProductPriceCriteria::class);
        $builder
            ->expects(self::once())
            ->method('create')
            ->willReturn($productPriceCriteria);

        self::assertSame($productPriceCriteria, $this->factory->create($product, $productUnit, $quantity, $currency));
    }

    public function testBuildFromProduct(): void
    {
        $product = (new ProductStub())->setId(42);
        $builder = $this->createMock(ProductPriceCriteriaBuilderInterface::class);

        $this->productPriceCriteriaBuilderRegistry
            ->expects(self::once())
            ->method('getBuilderForProduct')
            ->with($product)
            ->willReturn($builder);

        $builder
            ->expects(self::once())
            ->method('setProduct')
            ->with($product)
            ->willReturnSelf();

        self::assertSame($builder, $this->factory->buildFromProduct($product));
    }

    public function testCreateFromProductLineItem(): void
    {
        $productLineItem = $this->createMock(ProductLineItemInterface::class);
        $currency = 'USD';
        $productPriceCriteria = $this->createMock(ProductPriceCriteria::class);

        $this->productLineItemPriceCriteriaFactory
            ->expects(self::once())
            ->method('createFromProductLineItem')
            ->with($productLineItem, $currency)
            ->willReturn($productPriceCriteria);

        self::assertSame($productPriceCriteria, $this->factory->createFromProductLineItem($productLineItem, $currency));
    }

    public function testCreateListFromProductLineItemsWhenNoLineItems(): void
    {
        $this->productLineItemPriceCriteriaFactory
            ->expects(self::never())
            ->method('createFromProductLineItem');

        self::assertEquals([], $this->factory->createListFromProductLineItems([]));
    }

    public function testCreateListFromProductLineItems(): void
    {
        $productLineItem1 = $this->createMock(ProductLineItemInterface::class);
        $productLineItem2 = $this->createMock(ProductLineItemInterface::class);
        $currency = 'USD';
        $productPriceCriterion1 = $this->createMock(ProductPriceCriteria::class);
        $productPriceCriterion2 = $this->createMock(ProductPriceCriteria::class);

        $this->productLineItemPriceCriteriaFactory
            ->expects(self::exactly(2))
            ->method('createFromProductLineItem')
            ->willReturnMap([
                [$productLineItem1, $currency, $productPriceCriterion1],
                [$productLineItem2, $currency, $productPriceCriterion2],
            ]);

        self::assertSame(
            [10 => $productPriceCriterion1, 20 => $productPriceCriterion2],
            $this->factory->createListFromProductLineItems(
                [10 => $productLineItem1, 20 => $productLineItem2],
                $currency
            )
        );
    }

    public function testCreateListFromProductLineItemsWhenOneProductPriceCriteriaCannotBeCreated(): void
    {
        $productLineItem1 = $this->createMock(ProductLineItemInterface::class);
        $productLineItem2 = $this->createMock(ProductLineItemInterface::class);
        $currency = 'USD';
        $productPriceCriterion2 = $this->createMock(ProductPriceCriteria::class);

        $this->productLineItemPriceCriteriaFactory
            ->expects(self::exactly(2))
            ->method('createFromProductLineItem')
            ->willReturnMap([
                [$productLineItem1, $currency, null],
                [$productLineItem2, $currency, $productPriceCriterion2],
            ]);

        self::assertSame(
            [20 => $productPriceCriterion2],
            $this->factory->createListFromProductLineItems(
                [10 => $productLineItem1, 20 => $productLineItem2],
                $currency
            )
        );
    }

    public function testCreateListFromProductLineItemsWhenAllProductPriceCriteriaCannotBeCreated(): void
    {
        $productLineItem1 = $this->createMock(ProductLineItemInterface::class);
        $productLineItem2 = $this->createMock(ProductLineItemInterface::class);
        $currency = 'USD';

        $this->productLineItemPriceCriteriaFactory
            ->expects(self::exactly(2))
            ->method('createFromProductLineItem')
            ->willReturnMap([
                [$productLineItem1, $currency, null],
                [$productLineItem2, $currency, null],
            ]);

        self::assertSame(
            [],
            $this->factory->createListFromProductLineItems(
                [10 => $productLineItem1, 20 => $productLineItem2],
                $currency
            )
        );
    }

    public function testCreateListFromProductLineItemsWhenNotProductLineItem(): void
    {
        $object = new \stdClass();

        $this->expectExceptionObject(
            new \LogicException(
                sprintf(
                    '$lineItems were expected to contain only %s, got %s',
                    ProductLineItemInterface::class,
                    get_debug_type($object)
                )
            )
        );

        $this->factory->createListFromProductLineItems([$object]);
    }
}
