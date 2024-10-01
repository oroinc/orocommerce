<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider\PriceByMatchingCriteria;

use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceCollectionDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\PricingBundle\Provider\PriceByMatchingCriteria\ProductPriceByMatchingCriteriaProvider;
use Oro\Bundle\PricingBundle\Provider\PriceByMatchingCriteria\ProductPriceByMatchingCriteriaProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductPriceByMatchingCriteriaTest extends TestCase
{
    private ProductPriceByMatchingCriteriaProviderInterface|MockObject $innerProvider1;

    private ProductPriceByMatchingCriteriaProviderInterface|MockObject $innerProvider2;

    #[\Override]
    protected function setUp(): void
    {
        $this->innerProvider1 = $this->createMock(ProductPriceByMatchingCriteriaProviderInterface::class);
        $this->innerProvider2 = $this->createMock(ProductPriceByMatchingCriteriaProviderInterface::class);
    }

    public function testIsSupportedNoInnerProviders(): void
    {
        self::assertFalse(
            (new ProductPriceByMatchingCriteriaProvider([]))->isSupported(
                $this->createMock(ProductPriceCriteria::class),
                $this->createMock(ProductPriceCollectionDTO::class)
            )
        );
    }

    public function testGetProductPriceMatchingCriteriaWhenNoInnerProviders(): void
    {
        self::assertNull(
            (new ProductPriceByMatchingCriteriaProvider([]))->getProductPriceMatchingCriteria(
                $this->createMock(ProductPriceCriteria::class),
                $this->createMock(ProductPriceCollectionDTO::class)
            )
        );
    }

    public function testIsSupportedHasInnerProviders(): void
    {
        $productPriceByMatchingCriteriaProvider = new ProductPriceByMatchingCriteriaProvider(
            [$this->innerProvider1, $this->innerProvider2]
        );
        $productPriceCriteria = $this->createMock(ProductPriceCriteria::class);
        $productPriceCollection = $this->createMock(ProductPriceCollectionDTO::class);

        $this->innerProvider1
            ->expects(self::once())
            ->method('isSupported')
            ->with($productPriceCriteria, $productPriceCollection)
            ->willReturn(false);

        $this->innerProvider2
            ->expects(self::once())
            ->method('isSupported')
            ->with($productPriceCriteria, $productPriceCollection)
            ->willReturn(true);

        self::assertTrue(
            $productPriceByMatchingCriteriaProvider->isSupported($productPriceCriteria, $productPriceCollection)
        );
    }

    public function testGetProductPriceMatchingCriteriaWhenHasInnerProviders(): void
    {
        $provider = new ProductPriceByMatchingCriteriaProvider(
            [$this->innerProvider1, $this->innerProvider2]
        );
        $productPriceCriteria = $this->createMock(ProductPriceCriteria::class);
        $productPriceCollection = $this->createMock(ProductPriceCollectionDTO::class);

        $this->innerProvider1
            ->expects(self::once())
            ->method('isSupported')
            ->with($productPriceCriteria, $productPriceCollection)
            ->willReturn(true);
        $productPrice = $this->createMock(ProductPriceInterface::class);
        $this->innerProvider1
            ->expects(self::once())
            ->method('getProductPriceMatchingCriteria')
            ->with($productPriceCriteria, $productPriceCollection)
            ->willReturn($productPrice);

        $this->innerProvider2
            ->expects(self::never())
            ->method(self::anything());

        self::assertSame(
            $productPrice,
            $provider->getProductPriceMatchingCriteria(
                $productPriceCriteria,
                $productPriceCollection
            )
        );
    }
}
