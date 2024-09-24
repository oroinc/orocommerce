<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model\ProductPriceCriteriaDataExtractor;

use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaDataExtractor\ProductPriceCriteriaDataExtractor;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaDataExtractor\ProductPriceCriteriaDataExtractorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductPriceCriteriaDataExtractorTest extends TestCase
{
    private ProductPriceCriteriaDataExtractorInterface|MockObject $innerExtractor1;

    private ProductPriceCriteriaDataExtractorInterface|MockObject $innerExtractor2;

    #[\Override]
    protected function setUp(): void
    {
        $this->innerExtractor1 = $this->createMock(ProductPriceCriteriaDataExtractorInterface::class);
        $this->innerExtractor2 = $this->createMock(ProductPriceCriteriaDataExtractorInterface::class);
    }

    public function testIsSupportedNoInnerExtractors(): void
    {
        self::assertFalse(
            (new ProductPriceCriteriaDataExtractor([]))->isSupported(
                $this->createMock(ProductPriceCriteria::class)
            )
        );
    }

    public function testExtractCriteriaDataWhenNoInnerExtractors(): void
    {
        self::assertEquals(
            [
                ProductPriceCriteriaDataExtractorInterface::PRODUCT_IDS => [],
                ProductPriceCriteriaDataExtractorInterface::UNIT_CODES => [],
                ProductPriceCriteriaDataExtractorInterface::CURRENCIES => [],
            ],
            (new ProductPriceCriteriaDataExtractor([]))->extractCriteriaData(
                $this->createMock(ProductPriceCriteria::class)
            )
        );
    }

    public function testIsSupportedHasInnerExtractors(): void
    {
        $extractor = new ProductPriceCriteriaDataExtractor(
            [$this->innerExtractor1, $this->innerExtractor2]
        );
        $productPriceCriteria = $this->createMock(ProductPriceCriteria::class);

        $this->innerExtractor1
            ->expects(self::once())
            ->method('isSupported')
            ->with($productPriceCriteria)
            ->willReturn(false);

        $this->innerExtractor2
            ->expects(self::once())
            ->method('isSupported')
            ->with($productPriceCriteria)
            ->willReturn(true);

        self::assertTrue($extractor->isSupported($productPriceCriteria));
    }

    public function testExtractCriteriaDataWhenHasInnerExtractors(): void
    {
        $productLineItemPriceFactory = new ProductPriceCriteriaDataExtractor(
            [$this->innerExtractor1, $this->innerExtractor2]
        );
        $productPriceCriteria = $this->createMock(ProductPriceCriteria::class);

        $this->innerExtractor1
            ->expects(self::once())
            ->method('isSupported')
            ->with($productPriceCriteria)
            ->willReturn(true);

        $criteriaData = [
            ProductPriceCriteriaDataExtractorInterface::PRODUCT_IDS => [42],
            ProductPriceCriteriaDataExtractorInterface::UNIT_CODES => ['item'],
            ProductPriceCriteriaDataExtractorInterface::CURRENCIES => ['USD'],
        ];
        $this->innerExtractor1
            ->expects(self::once())
            ->method('extractCriteriaData')
            ->with($productPriceCriteria)
            ->willReturn($criteriaData);

        $this->innerExtractor2
            ->expects(self::never())
            ->method(self::anything());

        self::assertSame(
            $criteriaData,
            $productLineItemPriceFactory->extractCriteriaData($productPriceCriteria)
        );
    }
}
