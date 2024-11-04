<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model\ProductPriceCriteriaDataExtractor;

use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaDataExtractor\ProductPriceCriteriaDataExtractorInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaDataExtractor\SimpleProductPriceCriteriaDataExtractor;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\TestCase;

class SimpleProductPriceCriteriaDataExtractorTest extends TestCase
{
    private SimpleProductPriceCriteriaDataExtractor $extractor;

    #[\Override]
    protected function setUp(): void
    {
        $this->extractor = new SimpleProductPriceCriteriaDataExtractor();
    }

    public function testIsSupported(): void
    {
        self::assertTrue($this->extractor->isSupported($this->createMock(ProductPriceCriteria::class)));
    }

    public function testExtractCriteriaData(): void
    {
        $product = (new ProductStub())->setId(42);
        $productUnit = (new ProductUnit())->setCode('item');
        $quantity = 12.345;
        $currency = 'USD';
        $productPriceCriteria = new ProductPriceCriteria(
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
            $this->extractor->extractCriteriaData($productPriceCriteria)
        );
    }
}
