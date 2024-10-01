<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\ProductKit\ProductPriceCriteria\DataExtractor;

use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaDataExtractor\ProductPriceCriteriaDataExtractorInterface;
use Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\DataExtractor\ProductKitItemPriceCriteriaDataExtractor;
use Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\ProductKitItemPriceCriteria;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\TestCase;

class ProductKitItemPriceCriteriaDataExtractorTest extends TestCase
{
    private ProductKitItemPriceCriteriaDataExtractor $extractor;

    #[\Override]
    protected function setUp(): void
    {
        $this->extractor = new ProductKitItemPriceCriteriaDataExtractor();
    }

    public function testIsSupportedWhenNotSupported(): void
    {
        self::assertFalse($this->extractor->isSupported($this->createMock(ProductPriceCriteria::class)));
    }

    public function testIsSupportedWhenSupported(): void
    {
        self::assertTrue($this->extractor->isSupported($this->createMock(ProductKitItemPriceCriteria::class)));
    }

    public function testExtractCriteriaDataWhenNotSupported(): void
    {
        self::assertEquals(
            [
                ProductPriceCriteriaDataExtractorInterface::PRODUCT_IDS => [],
                ProductPriceCriteriaDataExtractorInterface::UNIT_CODES => [],
                ProductPriceCriteriaDataExtractorInterface::CURRENCIES => [],
            ],
            $this->extractor->extractCriteriaData($this->createMock(ProductPriceCriteria::class))
        );
    }

    public function testExtractCriteriaDataWhenSupported(): void
    {
        $product = (new ProductStub())->setId(42);
        $kitItem = new ProductKitItemStub(10);
        $productUnit = (new ProductUnit())->setCode('item');
        $quantity = 12.345;
        $currency = 'USD';
        $productKitItemPriceCriteria = new ProductKitItemPriceCriteria(
            $kitItem,
            $product,
            $productUnit,
            $quantity,
            $currency
        );

        self::assertEquals(
            [
                ProductPriceCriteriaDataExtractorInterface::PRODUCT_IDS => [$product->getId()],
                ProductPriceCriteriaDataExtractorInterface::UNIT_CODES => [$productUnit->getCode()],
                ProductPriceCriteriaDataExtractorInterface::CURRENCIES => [$currency],
            ],
            $this->extractor->extractCriteriaData($productKitItemPriceCriteria)
        );
    }
}
