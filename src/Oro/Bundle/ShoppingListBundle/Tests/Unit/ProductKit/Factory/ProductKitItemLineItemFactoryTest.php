<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\ProductKit\Factory;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;
use Oro\Bundle\ShoppingListBundle\ProductKit\Factory\ProductKitItemLineItemFactory;
use Oro\Bundle\ShoppingListBundle\ProductKit\Provider\ProductKitItemProductsProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductKitItemLineItemFactoryTest extends TestCase
{
    private ProductKitItemProductsProvider|MockObject $kitItemProductsProvider;

    private ProductKitItemLineItemFactory $factory;

    protected function setUp(): void
    {
        $this->kitItemProductsProvider = $this->createMock(ProductKitItemProductsProvider::class);

        $this->factory = new ProductKitItemLineItemFactory($this->kitItemProductsProvider);
    }

    public function testCreateKitItemLineItemWhenOptionalAndNoProducts(): void
    {
        $kitItem = (new ProductKitItem())
            ->setOptional(true);

        $this->kitItemProductsProvider
            ->expects(self::never())
            ->method('getFirstProductAvailableForPurchase');

        $kitItemLineItem = (new ProductKitItemLineItem())
            ->setKitItem($kitItem)
            ->setSortOrder($kitItem->getSortOrder());

        self::assertEquals($kitItemLineItem, $this->factory->createKitItemLineItem($kitItem));
    }

    public function testCreateKitItemLineItemWhenNotOptionalAndNoProducts(): void
    {
        $kitItem = (new ProductKitItem())
            ->setOptional(false);

        $this->kitItemProductsProvider
            ->expects(self::once())
            ->method('getFirstProductAvailableForPurchase')
            ->with($kitItem)
            ->willReturn(null);

        $kitItemLineItem = (new ProductKitItemLineItem())
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
        ProductKitItemLineItem $expected
    ): void {
        $kitItemProductUnit = (new ProductUnit())->setCode('item');
        $kitItem = (new ProductKitItem())
            ->setProductUnit($kitItemProductUnit)
            ->setSortOrder(11)
            ->setOptional(true);

        $this->kitItemProductsProvider
            ->expects(self::never())
            ->method('getFirstProductAvailableForPurchase');

        $expected
            ->setKitItem($kitItem)
            ->setSortOrder($kitItem->getSortOrder())
            ->setUnit($productUnit ?? $kitItemProductUnit);

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
                'expected' => new ProductKitItemLineItem()
            ],
            'with explicit product' => [
                'product' => $product,
                'productUnit' => null,
                'quantity' => null,
                'expected' => (new ProductKitItemLineItem())
                    ->setProduct($product),
            ],
            'with explicit product unit' => [
                'product' => $product,
                'productUnit' => $productUnit,
                'quantity' => null,
                'expected' => (new ProductKitItemLineItem())
                    ->setProduct($product)
                    ->setUnit($productUnit),
            ],
            'with explicit quantity' => [
                'product' => $product,
                'productUnit' => $productUnit,
                'quantity' => 12.345,
                'expected' => (new ProductKitItemLineItem())
                    ->setProduct($product)
                    ->setUnit($productUnit)
                    ->setQuantity(12.345),
            ],
        ];
    }

    public function testCreateKitItemLineItemWhenOptionalAndHasMinimumQuantity(): void
    {
        $kitItemProductUnit = (new ProductUnit())->setCode('item');
        $kitItem = (new ProductKitItem())
            ->setSortOrder(22)
            ->setOptional(true)
            ->setProductUnit($kitItemProductUnit)
            ->setMinimumQuantity(34.56);

        $product = (new ProductStub())
            ->setId(42);

        $expected = (new ProductKitItemLineItem())
            ->setKitItem($kitItem)
            ->setSortOrder($kitItem->getSortOrder())
            ->setProduct($product)
            ->setUnit($kitItemProductUnit);

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
        ProductKitItemLineItem $expected
    ): void {
        $kitItemProductUnit = (new ProductUnit())->setCode('item');
        $kitItem = (new ProductKitItem())
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
            ->method('getFirstProductAvailableForPurchase')
            ->with($kitItem)
            ->willReturn($firstProduct);

        $expected
            ->setKitItem($kitItem)
            ->setSortOrder($kitItem->getSortOrder())
            ->setProduct($product ?? $firstProduct)
            ->setUnit($productUnit ?? $kitItemProductUnit);

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
                'expected' => (new ProductKitItemLineItem())
                    ->setQuantity(1.0)
            ],
            'with explicit product' => [
                'product' => $product,
                'productUnit' => null,
                'quantity' => null,
                'expected' => (new ProductKitItemLineItem())
                    ->setProduct($product),
            ],
            'with explicit product unit' => [
                'product' => $product,
                'productUnit' => $productUnit,
                'quantity' => null,
                'expected' => (new ProductKitItemLineItem())
                    ->setProduct($product)
                    ->setUnit($productUnit)
                    ->setQuantity(0.001),
            ],
            'with explicit quantity' => [
                'product' => $product,
                'productUnit' => $productUnit,
                'quantity' => 12.345,
                'expected' => (new ProductKitItemLineItem())
                    ->setProduct($product)
                    ->setUnit($productUnit)
                    ->setQuantity(12.345),
            ],
        ];
    }

    public function testCreateKitItemLineItemWhenNotOptionalAndNoProductUnitPrecision(): void
    {
        $kitItemProductUnit = (new ProductUnit())->setCode('item');
        $kitItem = (new ProductKitItem())
            ->setSortOrder(22)
            ->setOptional(false)
            ->setProductUnit($kitItemProductUnit);

        $product = (new ProductStub())
            ->setId(42);

        $expected = (new ProductKitItemLineItem())
            ->setKitItem($kitItem)
            ->setSortOrder($kitItem->getSortOrder())
            ->setProduct($product)
            ->setUnit($kitItemProductUnit);

        self::assertEquals(
            $expected,
            $this->factory->createKitItemLineItem($kitItem, $product)
        );
    }

    public function testCreateKitItemLineItemWhenNotOptionalAndHasMinimumQuantity(): void
    {
        $kitItemProductUnit = (new ProductUnit())->setCode('item');
        $kitItem = (new ProductKitItem())
            ->setSortOrder(22)
            ->setOptional(false)
            ->setProductUnit($kitItemProductUnit)
            ->setMinimumQuantity(34.56);

        $product = (new ProductStub())
            ->setId(42);

        $expected = (new ProductKitItemLineItem())
            ->setKitItem($kitItem)
            ->setSortOrder($kitItem->getSortOrder())
            ->setProduct($product)
            ->setUnit($kitItemProductUnit)
            ->setQuantity($kitItem->getMinimumQuantity());

        self::assertEquals(
            $expected,
            $this->factory->createKitItemLineItem($kitItem, $product)
        );
    }
}
