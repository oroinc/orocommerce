<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\ProductKit;

use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\OrderBundle\ProductKit\Factory\OrderProductKitItemLineItemFactory;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\ProductKit\Provider\ProductKitItemProductsProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrderProductKitItemLineItemFactoryTest extends TestCase
{
    private ProductKitItemProductsProvider|MockObject $kitItemProductsProvider;

    private OrderProductKitItemLineItemFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->kitItemProductsProvider = $this->createMock(ProductKitItemProductsProvider::class);

        $this->factory = new OrderProductKitItemLineItemFactory($this->kitItemProductsProvider);
    }

    public function testCreateKitItemLineItemWhenOptionalAndNoProducts(): void
    {
        $kitItem = (new ProductKitItemStub())
            ->setOptional(true);

        $this->kitItemProductsProvider
            ->expects(self::never())
            ->method('getFirstAvailableProduct');

        $kitItemLineItem = (new OrderProductKitItemLineItem())
            ->setKitItem($kitItem)
            ->setSortOrder($kitItem->getSortOrder());

        self::assertEquals($kitItemLineItem, $this->factory->createKitItemLineItem($kitItem));
    }

    public function testCreateKitItemLineItemWhenNotOptionalAndNoProducts(): void
    {
        $kitItem = (new ProductKitItemStub())
            ->setOptional(false);

        $this->kitItemProductsProvider
            ->expects(self::once())
            ->method('getFirstAvailableProduct')
            ->with($kitItem)
            ->willReturn(null);

        $kitItemLineItem = (new OrderProductKitItemLineItem())
            ->setKitItem($kitItem)
            ->setSortOrder($kitItem->getSortOrder());

        self::assertEquals($kitItemLineItem, $this->factory->createKitItemLineItem($kitItem));
    }

    /**
     * @dataProvider createKitItemLineItemWhenOptionalDataProvider
     */
    public function testCreateKitItemLineItemWhenOptional(
        ?Product $product,
        ?ProductUnit $productUnit,
        ?float $quantity,
        OrderProductKitItemLineItem $expected
    ): void {
        $kitItemProductUnit = (new ProductUnit())->setCode('item');
        $kitItem = (new ProductKitItemStub())
            ->setProductUnit($kitItemProductUnit)
            ->setSortOrder(11)
            ->setOptional(true);

        $this->kitItemProductsProvider
            ->expects(self::never())
            ->method('getFirstAvailableProduct');

        $expected
            ->setKitItem($kitItem)
            ->setSortOrder($kitItem->getSortOrder())
            ->setProductUnit($productUnit ?? $kitItemProductUnit);

        self::assertEquals(
            $expected,
            $this->factory->createKitItemLineItem($kitItem, $product, $productUnit, $quantity)
        );
    }

    public function createKitItemLineItemWhenOptionalDataProvider(): array
    {
        $productUnit = (new ProductUnit())->setCode('each');
        $productUnitPrecision = (new ProductUnitPrecision())
            ->setUnit($productUnit);
        $product = (new ProductStub())
            ->setId(42)
            ->addUnitPrecision($productUnitPrecision);

        return [
            'no explicit arguments' => [
                'product' => null,
                'productUnit' => null,
                'quantity' => null,
                'expected' => new OrderProductKitItemLineItem(),
            ],
            'with explicit product' => [
                'product' => $product,
                'productUnit' => null,
                'quantity' => null,
                'expected' => (new OrderProductKitItemLineItem())
                    ->setProduct($product),
            ],
            'with explicit product unit' => [
                'product' => $product,
                'productUnit' => $productUnit,
                'quantity' => null,
                'expected' => (new OrderProductKitItemLineItem())
                    ->setProduct($product)
                    ->setProductUnit($productUnit),
            ],
            'with explicit quantity' => [
                'product' => $product,
                'productUnit' => $productUnit,
                'quantity' => 12.345,
                'expected' => (new OrderProductKitItemLineItem())
                    ->setProduct($product)
                    ->setProductUnit($productUnit)
                    ->setQuantity(12.345),
            ],
        ];
    }

    public function testCreateKitItemLineItemWhenOptionalAndHasMinimumQuantity(): void
    {
        $kitItemProductUnit = (new ProductUnit())->setCode('item');
        $kitItem = (new ProductKitItemStub())
            ->setSortOrder(22)
            ->setOptional(true)
            ->setProductUnit($kitItemProductUnit)
            ->setMinimumQuantity(34.56);

        $product = (new ProductStub())
            ->setId(42);

        $expected = (new OrderProductKitItemLineItem())
            ->setKitItem($kitItem)
            ->setSortOrder($kitItem->getSortOrder())
            ->setProduct($product)
            ->setProductUnit($kitItemProductUnit)
            ->setQuantity($kitItem->getMinimumQuantity());

        self::assertEquals(
            $expected,
            $this->factory->createKitItemLineItem($kitItem, $product)
        );
    }

    /**
     * @dataProvider createKitItemLineItemWhenNotOptionalDataProvider
     */
    public function testCreateKitItemLineItemWhenNotOptional(
        ?Product $product,
        ?ProductUnit $productUnit,
        ?float $quantity,
        OrderProductKitItemLineItem $expected
    ): void {
        $kitItemProductUnit = (new ProductUnit())->setCode('item');
        $kitItem = (new ProductKitItemStub())
            ->setSortOrder(11)
            ->setOptional(false)
            ->setProductUnit($kitItemProductUnit);

        $firstProductUnit = (new ProductUnit())->setCode('item');
        $firstProductUnitPrecision = (new ProductUnitPrecision())
            ->setUnit($firstProductUnit);
        $firstProduct = (new ProductStub())
            ->setId(442)
            ->addUnitPrecision($firstProductUnitPrecision);

        $this->kitItemProductsProvider
            ->method('getFirstAvailableProduct')
            ->with($kitItem)
            ->willReturn($firstProduct);

        $expected
            ->setKitItem($kitItem)
            ->setSortOrder($kitItem->getSortOrder())
            ->setProduct($product ?? $firstProduct)
            ->setProductUnit($productUnit ?? $kitItemProductUnit);

        self::assertEquals(
            $expected,
            $this->factory->createKitItemLineItem($kitItem, $product, $productUnit, $quantity)
        );
    }

    public function createKitItemLineItemWhenNotOptionalDataProvider(): array
    {
        $productUnit = (new ProductUnit())->setCode('each');
        $productUnitPrecision = (new ProductUnitPrecision())
            ->setUnit($productUnit)
            ->setPrecision(3);
        $product = (new ProductStub())
            ->setId(42)
            ->addUnitPrecision($productUnitPrecision);

        return [
            'no explicit arguments' => [
                'product' => null,
                'productUnit' => null,
                'quantity' => null,
                'expected' => (new OrderProductKitItemLineItem())
                    ->setQuantity(1.0),
            ],
            'with explicit product' => [
                'product' => $product,
                'productUnit' => null,
                'quantity' => null,
                'expected' => (new OrderProductKitItemLineItem())
                    ->setProduct($product),
            ],
            'with explicit product unit' => [
                'product' => $product,
                'productUnit' => $productUnit,
                'quantity' => null,
                'expected' => (new OrderProductKitItemLineItem())
                    ->setProduct($product)
                    ->setProductUnit($productUnit)
                    ->setQuantity(1.0),
            ],
            'with explicit quantity' => [
                'product' => $product,
                'productUnit' => $productUnit,
                'quantity' => 12.345,
                'expected' => (new OrderProductKitItemLineItem())
                    ->setProduct($product)
                    ->setProductUnit($productUnit)
                    ->setQuantity(12.345),
            ],
        ];
    }

    public function testCreateKitItemLineItemWhenNotOptionalAndNoProductUnitPrecision(): void
    {
        $kitItemProductUnit = (new ProductUnit())->setCode('item');
        $kitItem = (new ProductKitItemStub())
            ->setSortOrder(22)
            ->setOptional(false)
            ->setProductUnit($kitItemProductUnit);

        $product = (new ProductStub())
            ->setId(42);

        $expected = (new OrderProductKitItemLineItem())
            ->setKitItem($kitItem)
            ->setSortOrder($kitItem->getSortOrder())
            ->setProduct($product)
            ->setProductUnit($kitItemProductUnit);

        self::assertEquals(
            $expected,
            $this->factory->createKitItemLineItem($kitItem, $product)
        );
    }

    public function testCreateKitItemLineItemWhenNotOptionalAndHasMinimumQuantity(): void
    {
        $kitItemProductUnit = (new ProductUnit())->setCode('item');
        $kitItem = (new ProductKitItemStub())
            ->setSortOrder(22)
            ->setOptional(false)
            ->setProductUnit($kitItemProductUnit)
            ->setMinimumQuantity(34.56);

        $product = (new ProductStub())
            ->setId(42);

        $expected = (new OrderProductKitItemLineItem())
            ->setKitItem($kitItem)
            ->setSortOrder($kitItem->getSortOrder())
            ->setProduct($product)
            ->setProductUnit($kitItemProductUnit)
            ->setQuantity($kitItem->getMinimumQuantity());

        self::assertEquals(
            $expected,
            $this->factory->createKitItemLineItem($kitItem, $product)
        );
    }
}
