<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\ProductKit\ProductPriceCriteria;

use Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\ProductKitItemPriceCriteria;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductKitItemPriceCriteriaTest extends TestCase
{
    use EntityTrait;

    private function getProduct(int $id): Product
    {
        return $this->getEntity(Product::class, ['id' => $id]);
    }

    /**
     * @dataProvider productKitItemPriceCriteriaDataProvider
     */
    public function testProductKitItemPriceCriteria(mixed $quantity, string $currency): void
    {
        $productKit = $this->getProduct(42);
        $productUnit = (new ProductUnit())->setCode('kg');
        $instance = new ProductKitItemPriceCriteria(
            new ProductKitItemStub(4242),
            $productKit,
            $productUnit,
            $quantity,
            $currency
        );

        self::assertInstanceOf(ProductKitItemPriceCriteria::class, $instance);
        self::assertEquals($productKit, $instance->getProduct());
        self::assertEquals($productUnit, $instance->getProductUnit());
        self::assertEquals($quantity, $instance->getQuantity());
        self::assertEquals($currency, $instance->getCurrency());
        self::assertEquals('4242-42-kg-' . $quantity . '-' . $currency, $instance->getIdentifier());
    }

    public function productKitItemPriceCriteriaDataProvider(): array
    {
        return [
            [0, 'USD'],
            ['0', 'EUR'],
            [1, 'USD'],
            [1.1, 'EUR'],
            ['1', 'USD'],
            ['1.1', 'EUR'],
        ];
    }

    /**
     * @dataProvider constructorKitItemExceptionDataProvider
     */
    public function testConstructorKitItemException(?ProductKitItem $kitItem): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ProductKitItem must have an id.');

        new ProductKitItemPriceCriteria(
            $kitItem,
            new Product(),
            (new ProductUnit())->setCode('kg'),
            1,
            'USD'
        );
    }

    public function constructorKitItemExceptionDataProvider(): array
    {
        return [
            [null],
            [new ProductKitItem()],
        ];
    }

    /**
     * @dataProvider constructorProductExceptionDataProvider
     */
    public function testConstructorProductException(?Product $product): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Product must have id.');

        new ProductKitItemPriceCriteria(
            new ProductKitItemStub(4242),
            $product,
            (new ProductUnit())->setCode('kg'),
            1,
            'USD'
        );
    }

    public function constructorProductExceptionDataProvider(): array
    {
        return [
            [null],
            [new Product()],
        ];
    }

    /**
     * @dataProvider constructorProductUnitExceptionDataProvider
     */
    public function testConstructorProductUnitException(?ProductUnit $productUnit): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ProductUnit must have code.');

        new ProductKitItemPriceCriteria(
            new ProductKitItemStub(4242),
            $this->getProduct(42),
            $productUnit,
            1,
            'USD'
        );
    }

    public function constructorProductUnitExceptionDataProvider(): array
    {
        return [
            [null],
            [new ProductUnit()],
        ];
    }

    /**
     * @dataProvider getConstructorQuantityExceptionDataProvider
     */
    public function testConstructorQuantityException(?float $quantity): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Quantity must be numeric and more than or equal zero.');

        new ProductKitItemPriceCriteria(
            new ProductKitItemStub(4242),
            $this->getProduct(42),
            (new ProductUnit())->setCode('kg'),
            $quantity,
            'USD'
        );
    }

    public function getConstructorQuantityExceptionDataProvider(): array
    {
        return [
            [null],
            [-1],
        ];
    }

    /**
     * @dataProvider getConstructorCurrencyExceptionDataProvider
     */
    public function testConstructorCurrencyException(?string $currency): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency must be non-empty string.');

        new ProductKitItemPriceCriteria(
            new ProductKitItemStub(4242),
            $this->getProduct(42),
            (new ProductUnit())->setCode('kg'),
            1,
            $currency
        );
    }

    public function getConstructorCurrencyExceptionDataProvider(): array
    {
        return [
            [null],
            [''],
        ];
    }

    public function testGetIdentifier(): void
    {
        $product = $this->getProduct(150);

        $productUnit = new ProductUnit();
        $productUnit->setCode('kg');

        $productPriceCriteria = new ProductKitItemPriceCriteria(
            new ProductKitItemStub(4242),
            $product,
            $productUnit,
            42,
            'USD'
        );

        self::assertEquals('4242-150-kg-42-USD', $productPriceCriteria->getIdentifier());
    }
}
