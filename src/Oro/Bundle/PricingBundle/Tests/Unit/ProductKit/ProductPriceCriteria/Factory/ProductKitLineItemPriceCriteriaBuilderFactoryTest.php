<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\ProductKit\ProductPriceCriteria\Factory;

use Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\Builder\ProductKitPriceCriteriaBuilderInterface;
use Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\Factory\ProductKitLineItemPriceCriteriaBuilderFactory;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductKitItemLineItemsAwareStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductKitItemLineItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductLineItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\TestCase;

class ProductKitLineItemPriceCriteriaBuilderFactoryTest extends TestCase
{
    private const USD = 'USD';

    private ProductKitPriceCriteriaBuilderInterface $productKitPriceCriteriaBuilder;

    private ProductKitLineItemPriceCriteriaBuilderFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->productKitPriceCriteriaBuilder = $this->createMock(ProductKitPriceCriteriaBuilderInterface::class);
        $this->factory = new ProductKitLineItemPriceCriteriaBuilderFactory($this->productKitPriceCriteriaBuilder);
    }

    /**
     * @dataProvider invalidLineItemDataProvider
     */
    public function testCreateFromProductLineItemWhenInvalidLineItem(ProductLineItemInterface $lineItem): void
    {
        $this->productKitPriceCriteriaBuilder
            ->expects(self::never())
            ->method(self::anything());

        self::assertNull($this->factory->createFromProductLineItem($lineItem, self::USD));
    }

    public function invalidLineItemDataProvider(): \Generator
    {
        yield [new ProductLineItemStub(10)];

        yield [
            (new ProductLineItemStub(10))
                ->setUnit(new ProductUnit())
                ->setQuantity(12.3456),
        ];

        yield [
            (new ProductLineItemStub(10))
                ->setProduct(new Product())
                ->setQuantity(12.3456),
        ];

        yield [
            (new ProductLineItemStub(10))
                ->setProduct(new Product())
                ->setUnit(new ProductUnit())
                ->setQuantity(null),
        ];

        yield [
            (new ProductLineItemStub(10))
                ->setProduct(new Product())
                ->setUnit(new ProductUnit())
                ->setQuantity(-1.0),
        ];
    }

    public function testCreateFromProductLineItem(): void
    {
        $productUnitItem = (new ProductUnit())->setCode('item');
        $productUnitEach = (new ProductUnit())->setCode('each');
        $kitItemLineItem1Product = (new ProductStub())->setId(1);
        $kitItem1 = new ProductKitItemStub(11);
        $kitItemLineItem1 = (new ProductKitItemLineItemStub(10))
            ->setKitItem($kitItem1)
            ->setProduct($kitItemLineItem1Product)
            ->setUnit($productUnitItem)
            ->setQuantity(11);
        $kitItemLineItem2Product = (new ProductStub())->setId(2);
        $kitItem2 = new ProductKitItemStub(22);
        $kitItemLineItem2 = (new ProductKitItemLineItemStub(20))
            ->setKitItem($kitItem2)
            ->setProduct($kitItemLineItem2Product)
            ->setUnit($productUnitEach)
            ->setQuantity(22);
        $productKit = (new ProductStub())->setId(100)->setType(Product::TYPE_KIT);
        $kitLineItem = (new ProductKitItemLineItemsAwareStub(42))
            ->setProduct($productKit)
            ->setUnit($productUnitEach)
            ->setQuantity(111)
            ->addKitItemLineItem($kitItemLineItem1)
            ->addKitItemLineItem($kitItemLineItem2);

        $this->productKitPriceCriteriaBuilder
            ->expects(self::once())
            ->method('setProduct')
            ->with($productKit)
            ->willReturnSelf();

        $this->productKitPriceCriteriaBuilder
            ->expects(self::once())
            ->method('setProductUnit')
            ->with($productUnitEach)
            ->willReturnSelf();

        $this->productKitPriceCriteriaBuilder
            ->expects(self::once())
            ->method('setQuantity')
            ->with($kitLineItem->getQuantity())
            ->willReturnSelf();

        $this->productKitPriceCriteriaBuilder
            ->expects(self::once())
            ->method('setCurrency')
            ->with(self::USD)
            ->willReturnSelf();

        $this->productKitPriceCriteriaBuilder
            ->expects(self::exactly(2))
            ->method('addKitItemProduct')
            ->withConsecutive(
                [
                    $kitItemLineItem1->getKitItem(),
                    $kitItemLineItem1->getProduct(),
                    $kitItemLineItem1->getProductUnit(),
                    $kitItemLineItem1->getQuantity(),
                ],
                [
                    $kitItemLineItem2->getKitItem(),
                    $kitItemLineItem2->getProduct(),
                    $kitItemLineItem2->getProductUnit(),
                    $kitItemLineItem2->getQuantity(),
                ]
            )
            ->willReturnSelf();

        self::assertEquals(
            $this->productKitPriceCriteriaBuilder,
            $this->factory->createFromProductLineItem($kitLineItem, self::USD)
        );
    }

    public function testCreateFromProductLineItemWhenNoKitItemLineItems(): void
    {
        $productUnitEach = (new ProductUnit())->setCode('each');
        $productKit = (new ProductStub())->setId(100)->setType(Product::TYPE_KIT);
        $kitLineItem = (new ProductKitItemLineItemsAwareStub(42))
            ->setProduct($productKit)
            ->setUnit($productUnitEach)
            ->setQuantity(111);

        $this->productKitPriceCriteriaBuilder
            ->expects(self::once())
            ->method('setProduct')
            ->with($productKit)
            ->willReturnSelf();

        $this->productKitPriceCriteriaBuilder
            ->expects(self::once())
            ->method('setProductUnit')
            ->with($productUnitEach)
            ->willReturnSelf();

        $this->productKitPriceCriteriaBuilder
            ->expects(self::once())
            ->method('setQuantity')
            ->with($kitLineItem->getQuantity())
            ->willReturnSelf();

        $this->productKitPriceCriteriaBuilder
            ->expects(self::once())
            ->method('setCurrency')
            ->with(self::USD)
            ->willReturnSelf();

        $this->productKitPriceCriteriaBuilder
            ->expects(self::never())
            ->method('addKitItemProduct');

        self::assertEquals(
            $this->productKitPriceCriteriaBuilder,
            $this->factory->createFromProductLineItem($kitLineItem, self::USD)
        );
    }

    /**
     * @dataProvider invalidKitItemLineItemDataProvider
     */
    public function testCreateFromProductLineItemWhenHasInvalidKitItemLineItem(
        ProductKitItemLineItemInterface $invalidKitItemLineItem
    ): void {
        $productUnitEach = (new ProductUnit())->setCode('each');
        $kitItemLineItem2Product = (new ProductStub())->setId(2);
        $kitItem2 = new ProductKitItemStub(22);
        $kitItemLineItem2 = (new ProductKitItemLineItemStub(20))
            ->setKitItem($kitItem2)
            ->setProduct($kitItemLineItem2Product)
            ->setUnit($productUnitEach)
            ->setQuantity(22);
        $productKit = (new ProductStub())->setId(100)->setType(Product::TYPE_KIT);
        $kitLineItem = (new ProductKitItemLineItemsAwareStub(42))
            ->setProduct($productKit)
            ->setUnit($productUnitEach)
            ->setQuantity(111)
            ->addKitItemLineItem($invalidKitItemLineItem)
            ->addKitItemLineItem($kitItemLineItem2);

        $this->productKitPriceCriteriaBuilder
            ->expects(self::once())
            ->method('setProduct')
            ->with($productKit)
            ->willReturnSelf();

        $this->productKitPriceCriteriaBuilder
            ->expects(self::once())
            ->method('setProductUnit')
            ->with($productUnitEach)
            ->willReturnSelf();

        $this->productKitPriceCriteriaBuilder
            ->expects(self::once())
            ->method('setQuantity')
            ->with($kitLineItem->getQuantity())
            ->willReturnSelf();

        $this->productKitPriceCriteriaBuilder
            ->expects(self::once())
            ->method('setCurrency')
            ->with(self::USD)
            ->willReturnSelf();

        $this->productKitPriceCriteriaBuilder
            ->expects(self::once())
            ->method('addKitItemProduct')
            ->with(
                $kitItemLineItem2->getKitItem(),
                $kitItemLineItem2->getProduct(),
                $kitItemLineItem2->getProductUnit(),
                $kitItemLineItem2->getQuantity(),
            )
            ->willReturnSelf();

        self::assertEquals(
            $this->productKitPriceCriteriaBuilder,
            $this->factory->createFromProductLineItem($kitLineItem, self::USD)
        );
    }

    public function invalidKitItemLineItemDataProvider(): \Generator
    {
        yield [new ProductKitItemLineItemStub(10)];

        yield [
            (new ProductKitItemLineItemStub(10))
                ->setProduct(new Product())
                ->setUnit(new ProductUnit())
                ->setQuantity(12.3456),
        ];

        yield [
            (new ProductKitItemLineItemStub(10))
                ->setKitItem(new ProductKitItemStub(1))
                ->setUnit(new ProductUnit())
                ->setQuantity(12.3456),
        ];

        yield [
            (new ProductKitItemLineItemStub(10))
                ->setKitItem(new ProductKitItemStub(1))
                ->setProduct(new Product())
                ->setQuantity(12.3456),
        ];

        yield [
            (new ProductKitItemLineItemStub(10))
                ->setKitItem(new ProductKitItemStub(1))
                ->setProduct(new Product())
                ->setUnit(new ProductUnit())
                ->setQuantity(null),
        ];

        yield [
            (new ProductKitItemLineItemStub(10))
                ->setKitItem(new ProductKitItemStub(1))
                ->setProduct(new Product())
                ->setUnit(new ProductUnit())
                ->setQuantity(-1.0),
        ];
    }
}
