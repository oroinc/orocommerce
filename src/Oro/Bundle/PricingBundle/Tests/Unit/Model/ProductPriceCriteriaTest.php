<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\TestCase;

class ProductPriceCriteriaTest extends TestCase
{
    use EntityTrait;

    private function getProduct(int $id): Product
    {
        return $this->getEntity(Product::class, ['id' => $id]);
    }

    /**
     * @dataProvider productPriceCriteriaDataProvider
     */
    public function testProductPriceCriteria(mixed $quantity, string $currency): void
    {
        $instance = new ProductPriceCriteria(
            $this->getProduct(42),
            (new ProductUnit())->setCode('kg'),
            $quantity,
            $currency
        );

        self::assertInstanceOf(ProductPriceCriteria::class, $instance);
        self::assertEquals($this->getProduct(42), $instance->getProduct());
        self::assertEquals((new ProductUnit())->setCode('kg'), $instance->getProductUnit());
        self::assertEquals($quantity, $instance->getQuantity());
        self::assertEquals($currency, $instance->getCurrency());
    }

    public function productPriceCriteriaDataProvider(): array
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

    public function testConstructorProductException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Product must have id.');

        new ProductPriceCriteria(new Product(), (new ProductUnit())->setCode('kg'), 1, 'USD');
    }

    public function testConstructorProductUnitException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ProductUnit must have code.');

        new ProductPriceCriteria($this->getProduct(42), new ProductUnit(), 1, 'USD');
    }

    /**
     * @dataProvider getConstructorQuantityExceptionDataProvider
     */
    public function testConstructorQuantityException(?float $quantity): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Quantity must be numeric and more than or equal zero.');

        new ProductPriceCriteria(
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

        new ProductPriceCriteria($this->getProduct(42), (new ProductUnit())->setCode('kg'), 1, $currency);
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

        $productPriceCriteria = new ProductPriceCriteria($product, $productUnit, 42, 'USD');

        self::assertEquals('150-kg-42-USD', $productPriceCriteria->getIdentifier());
    }
}
