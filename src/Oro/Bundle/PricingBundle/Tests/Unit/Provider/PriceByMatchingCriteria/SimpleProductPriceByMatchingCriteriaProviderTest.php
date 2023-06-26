<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider\PriceByMatchingCriteria;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceCollectionDTO;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Provider\PriceByMatchingCriteria\SimpleProductPriceByMatchingCriteriaProvider;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\TestCase;

class SimpleProductPriceByMatchingCriteriaProviderTest extends TestCase
{
    private const USD = 'USD';

    private SimpleProductPriceByMatchingCriteriaProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new SimpleProductPriceByMatchingCriteriaProvider();
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
            ->willReturn([]);

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

        $productPriceCollection
            ->expects(self::once())
            ->method('getMatchingByCriteria')
            ->with($product->getId(), $productUnitItem->getCode(), self::USD)
            ->willReturn([new ProductPriceDTO($product, Price::create(1.2345, self::USD), 10, $productUnitItem)]);

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
            ->willReturn([$productPriceDTO]);

        self::assertSame(
            $productPriceDTO,
            $this->provider->getProductPriceMatchingCriteria($productPriceCriteria, $productPriceCollection)
        );
    }
}
