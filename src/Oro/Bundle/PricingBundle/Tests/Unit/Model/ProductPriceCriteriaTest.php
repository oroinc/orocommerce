<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Component\Testing\ReflectionUtil;

class ProductPriceCriteriaTest extends \PHPUnit\Framework\TestCase
{
    private function getProduct(int $id): Product
    {
        $product = new Product();
        ReflectionUtil::setId($product, $id);

        return $product;
    }

    /**
     * @dataProvider productPriceCriteriaDataProvider
     */
    public function testProductPriceCriteria(mixed $quantity, string $currency)
    {
        $instance = new ProductPriceCriteria(
            $this->getProduct(42),
            (new ProductUnit())->setCode('kg'),
            $quantity,
            $currency
        );

        $this->assertInstanceOf(ProductPriceCriteria::class, $instance);
        $this->assertEquals($this->getProduct(42), $instance->getProduct());
        $this->assertEquals((new ProductUnit())->setCode('kg'), $instance->getProductUnit());
        $this->assertEquals($quantity, $instance->getQuantity());
        $this->assertEquals($currency, $instance->getCurrency());
    }

    public function productPriceCriteriaDataProvider(): array
    {
        return [
            [0, 'USD'],
            ['0', 'EUR'],
            [1, 'USD'],
            [1.1, 'EUR'],
            ['1', 'USD'],
            ['1.1', 'EUR']
        ];
    }

    public function testConstructorProductException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Product must have id.');

        new ProductPriceCriteria(new Product(), (new ProductUnit())->setCode('kg'), 1, 'USD');
    }

    public function testConstructorProductUnitException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ProductUnit must have code.');

        new ProductPriceCriteria($this->getProduct(42), new ProductUnit(), 1, 'USD');
    }

    /**
     * @dataProvider constructorExceptionDataProvider
     */
    public function testConstructorQuantityException(mixed $quantity, string $currency)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Quantity must be numeric and more than or equal zero.');

        new ProductPriceCriteria($this->getProduct(42), (new ProductUnit())->setCode('kg'), $quantity, $currency);
    }

    public function testConstructorCurrencyException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency must be non-empty string.');

        new ProductPriceCriteria($this->getProduct(42), (new ProductUnit())->setCode('kg'), 1, '');
    }

    public function constructorExceptionDataProvider(): array
    {
        return [
            [-1, 'USD'],
            ['', 'EUR'],
            [null, 'USD'],
            ['1a', 'EUR']
        ];
    }

    public function testGetIdentifier()
    {
        $product = $this->getProduct(150);

        $productUnit = new ProductUnit();
        $productUnit->setCode('kg');

        $productPriceCriteria = new ProductPriceCriteria($product, $productUnit, 42, 'USD');

        $this->assertEquals('150-kg-42-USD', $productPriceCriteria->getIdentifier());
    }
}
