<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\ProductKit\ProductPriceCriteria;

use Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\ProductKitItemPriceCriteria;
use Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\ProductKitPriceCriteria;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\TestCase;

class ProductKitPriceCriteriaTest extends TestCase
{
    use EntityTrait;

    private function getProduct(int $id): Product
    {
        return $this->getEntity(Product::class, ['id' => $id]);
    }

    /**
     * @dataProvider productKitPriceCriteriaDataProvider
     */
    public function testProductKitPriceCriteria(mixed $quantity, string $currency): void
    {
        $productKit = $this->getProduct(42);
        $productUnit = (new ProductUnit())->setCode('kg');
        $instance = new ProductKitPriceCriteria(
            $productKit,
            $productUnit,
            $quantity,
            $currency
        );

        $kitItemInstance = new ProductKitItemPriceCriteria(
            new ProductKitItemStub(4242),
            $this->getProduct(10),
            (new ProductUnit())->setCode('item'),
            12.345,
            'USD'
        );

        self::assertInstanceOf(ProductKitPriceCriteria::class, $instance);
        self::assertEquals($productKit, $instance->getProduct());
        self::assertEquals($productUnit, $instance->getProductUnit());
        self::assertEquals($quantity, $instance->getQuantity());
        self::assertEquals($currency, $instance->getCurrency());
        self::assertEquals([], $instance->getKitItemsProductsPriceCriteria());
        self::assertEquals('42-kg-' . $quantity . '-' . $currency . '-[]', $instance->getIdentifier());

        $instance->addKitItemProductPriceCriteria($kitItemInstance);

        self::assertEquals(
            [$kitItemInstance->getKitItem()->getId() => $kitItemInstance],
            $instance->getKitItemsProductsPriceCriteria()
        );
        self::assertEquals(
            '42-kg-' . $quantity . '-' . $currency . '-[4242-10-item-12.345-USD]',
            $instance->getIdentifier()
        );
    }

    public function productKitPriceCriteriaDataProvider(): array
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

        new ProductKitPriceCriteria(new Product(), (new ProductUnit())->setCode('kg'), 1, 'USD');
    }

    public function testConstructorProductUnitException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ProductUnit must have code.');

        new ProductKitPriceCriteria($this->getProduct(42), new ProductUnit(), 1, 'USD');
    }

    /**
     * @dataProvider getConstructorQuantityExceptionDataProvider
     */
    public function testConstructorQuantityException(?float $quantity): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Quantity must be numeric and more than or equal zero.');

        new ProductKitPriceCriteria(
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

        new ProductKitPriceCriteria($this->getProduct(42), (new ProductUnit())->setCode('kg'), 1, $currency);
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

        $productPriceCriteria = new ProductKitPriceCriteria($product, $productUnit, 42, 'USD');

        self::assertEquals('150-kg-42-USD-[]', $productPriceCriteria->getIdentifier());
    }

    public function testAddKitItemProductPriceCriteriaWhenAlreadyExists(): void
    {
        $instance = new ProductKitPriceCriteria(
            $this->getProduct(42),
            (new ProductUnit())->setCode('kg'),
            42.4242,
            'USD'
        );

        $kitItem = new ProductKitItemStub(4242);
        $kitItemInstance = new ProductKitItemPriceCriteria(
            $kitItem,
            $this->getProduct(10),
            (new ProductUnit())->setCode('kg'),
            12.345,
            'USD'
        );
        $instance->addKitItemProductPriceCriteria($kitItemInstance);

        $this->expectExceptionObject(
            new \LogicException(
                sprintf(
                    'Product price criteria for the %s #%d is already added and cannot be changed',
                    ProductKitItem::class,
                    $kitItem->getId()
                )
            )
        );

        $instance->addKitItemProductPriceCriteria($kitItemInstance);
    }
}
