<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider\PriceByMatchingCriteria;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceCollectionDTO;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Provider\PriceByMatchingCriteria\SimpleProductPriceByMatchingCriteriaProvider;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\TestCase;

class SimpleProductPriceByMatchingCriteriaProviderTest extends TestCase
{
    private const USD = 'USD';

    private SimpleProductPriceByMatchingCriteriaProvider $provider;

    private ConfigManager $configManager;
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->provider = new SimpleProductPriceByMatchingCriteriaProvider($this->configManager);
    }

    public function testIsSupported(): void
    {
        self::assertTrue(
            $this->provider->isSupported(
                $this->createMock(ProductPriceCriteria::class),
                new ProductPriceCollectionDTO()
            )
        );
    }

    public function testGetProductPriceMatchingCriteriaWhenNoMatchingPrices(): void
    {
        $product = (new ProductStub())->setId(42);
        $productUnitItem = (new ProductUnit())->setCode('item');
        $productPriceCriteria = new ProductPriceCriteria($product, $productUnitItem, 1.2345, self::USD);
        $productPriceCollection = $this->createMock(ProductPriceCollectionDTO::class);

        $productPriceCollection
            ->expects(self::once())
            ->method('getMatchingByCriteria')
            ->with($product->getId(), $productUnitItem->getCode(), self::USD)
            ->willReturn(new \ArrayIterator([]));

        self::assertNull(
            $this->provider->getProductPriceMatchingCriteria($productPriceCriteria, $productPriceCollection)
        );
    }

    public function testGetProductPriceMatchingCriteriaWhenNoMatchingPriceForQuantity(): void
    {
        $product = (new ProductStub())->setId(42);
        $productUnitItem = (new ProductUnit())->setCode('item');
        $productPriceCriteria = new ProductPriceCriteria($product, $productUnitItem, 1.2345, self::USD);
        $productPriceCollection = $this->createMock(ProductPriceCollectionDTO::class);
        $productPrice = new ProductPriceDTO($product, Price::create(1.2345, self::USD), 10, $productUnitItem);

        $productPriceCollection
            ->expects(self::once())
            ->method('getMatchingByCriteria')
            ->with($product->getId(), $productUnitItem->getCode(), self::USD)
            ->willReturn(new \ArrayIterator([$productPrice]));

        self::assertNull(
            $this->provider->getProductPriceMatchingCriteria($productPriceCriteria, $productPriceCollection)
        );
    }

    public function testGetProductPriceMatchingCriteriaWhenHasMatchingPriceForQuantity(): void
    {
        $product = (new ProductStub())->setId(42);
        $productUnitItem = (new ProductUnit())->setCode('item');
        $productPriceCriteria = new ProductPriceCriteria($product, $productUnitItem, 1.2345, self::USD);
        $productPriceCollection = $this->createMock(ProductPriceCollectionDTO::class);

        $productPriceDTO = new ProductPriceDTO($product, Price::create(1.2345, self::USD), 1, $productUnitItem);
        $productPriceCollection
            ->expects(self::once())
            ->method('getMatchingByCriteria')
            ->with($product->getId(), $productUnitItem->getCode(), self::USD)
            ->willReturn(new \ArrayIterator([$productPriceDTO]));

        self::assertSame(
            $productPriceDTO,
            $this->provider->getProductPriceMatchingCriteria($productPriceCriteria, $productPriceCollection)
        );
    }

    /**
     * @dataProvider getProductQuantitySettingsDataProvider
     */
    public function testGetProductPriceMatchingCriteriaWithSetting(
        ?float $expectedProductPrice,
        int $precision,
        float $quantity,
        bool $fractionalQuantityLessThenUnit,
        bool $fractionalQuantityLessThenMinimumPriced,
        bool $quantityLessThenMinimumPriced,
    ): void {
        $productUnitItem = (new ProductUnit())->setCode('item');
        $unitPrecisions = (new ProductUnitPrecision())->setUnit($productUnitItem)->setPrecision($precision);
        $product = (new ProductStub())
            ->setId(42)
            ->setPrimaryUnitPrecision($unitPrecisions);

        $productPriceCriteria = new ProductPriceCriteria($product, $productUnitItem, $quantity, self::USD);

        $this->configManager
            ->expects(self::any())
            ->method('get')
            ->withConsecutive(
                ['oro_pricing.fractional_quantity_less_then_unit_price_calculation'],
                ['oro_pricing.fractional_quantity_less_then_minimum_priced_price_calculation'],
                ['oro_pricing.quantity_less_then_minimum_priced_price_calculation'],
            )
            ->willReturnOnConsecutiveCalls(
                $fractionalQuantityLessThenUnit,
                $fractionalQuantityLessThenMinimumPriced,
                $quantityLessThenMinimumPriced
            );

        $productPriceCollection = $this->createMock(ProductPriceCollectionDTO::class);

        $productPriceDTO = [
           new ProductPriceDTO($product, Price::create(15.00, self::USD), 10, $productUnitItem),
           new ProductPriceDTO($product, Price::create(12.00, self::USD), 15, $productUnitItem),
           new ProductPriceDTO($product, Price::create(10.00, self::USD), 20, $productUnitItem),
        ];

        $productPriceCollection
            ->expects(self::once())
            ->method('getMatchingByCriteria')
            ->with($product->getId(), $productUnitItem->getCode(), self::USD)
            ->willReturn(new \ArrayIterator($productPriceDTO));

        $result = $this->provider->getProductPriceMatchingCriteria($productPriceCriteria, $productPriceCollection);

        self::assertSame(
            $expectedProductPrice,
            $result?->getPrice()->getValue()
        );
    }

    public function getProductQuantitySettingsDataProvider()
    {
        return [
            [
                'expectedProductPrice' => 15,
                'precision' => 1,
                'quantity' => 0.5,
                'fractionalQuantityLessThenUnit' => true,
                'fractionalQuantityLessThenMinimumPriced' => false,
                'quantityLessThenMinimumPriced ' => false,
            ],
            [
                'expectedProductPrice' => null,
                'precision' => 1,
                'quantity' => 1.5,
                'fractionalQuantityLessThenUnit' => true,
                'fractionalQuantityLessThenMinimumPriced' => false,
                'quantityLessThenMinimumPriced ' => false,
            ],
            [
                'expectedProductPrice' => null,
                'precision' => 0,
                'quantity' => 0.5,
                'fractionalQuantityLessThenUnit' => true,
                'fractionalQuantityLessThenMinimumPriced' => false,
                'quantityLessThenMinimumPriced ' => false,
            ],
            [
                'expectedProductPrice' => 15,
                'precision' => 1,
                'quantity' => 0.5,
                'fractionalQuantityLessThenUnit' => false,
                'fractionalQuantityLessThenMinimumPriced' => true,
                'quantityLessThenMinimumPriced ' => false,
            ],
            [
                'expectedProductPrice' => 15,
                'precision' => 1,
                'quantity' => 1.5,
                'fractionalQuantityLessThenUnit' => false,
                'fractionalQuantityLessThenMinimumPriced' => true,
                'quantityLessThenMinimumPriced ' => false,
            ],
            [
                'expectedProductPrice' => null,
                'precision' => 0,
                'quantity' => 1.5,
                'fractionalQuantityLessThenUnit' => false,
                'fractionalQuantityLessThenMinimumPriced' => true,
                'quantityLessThenMinimumPriced ' => false,
            ],
            [
                'expectedProductPrice' => 15,
                'precision' => 0,
                'quantity' => 5,
                'fractionalQuantityLessThenUnit' => false,
                'fractionalQuantityLessThenMinimumPriced' => false,
                'quantityLessThenMinimumPriced ' => true,
            ],
            [
                'expectedProductPrice' => null,
                'precision' => 1,
                'quantity' => 8,
                'fractionalQuantityLessThenUnit' => false,
                'fractionalQuantityLessThenMinimumPriced' => false,
                'quantityLessThenMinimumPriced ' => true,
            ],
            [
                'expectedProductPrice' => 15,
                'precision' => 0,
                'quantity' => 12,
                'fractionalQuantityLessThenUnit' => true,
                'fractionalQuantityLessThenMinimumPriced' => true,
                'quantityLessThenMinimumPriced ' => true,
            ],
            [
                'expectedProductPrice' => 12,
                'precision' => 0,
                'quantity' => 16,
                'fractionalQuantityLessThenUnit' => true,
                'fractionalQuantityLessThenMinimumPriced' => true,
                'quantityLessThenMinimumPriced ' => true,
            ],
            [
                'expectedProductPrice' => 15,
                'precision' => 1,
                'quantity' => 12,
                'fractionalQuantityLessThenUnit' => true,
                'fractionalQuantityLessThenMinimumPriced' => true,
                'quantityLessThenMinimumPriced ' => true,
            ],
        ];
    }
}
