<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaDataExtractor;

use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;

/**
 * Extracts data from {@see ProductPriceCriteria}.
 */
class ProductPriceCriteriaDataExtractor implements ProductPriceCriteriaDataExtractorInterface
{
    /** @var iterable<ProductPriceCriteriaDataExtractorInterface> */
    private iterable $innerExtractors;

    /**
     * @param iterable<ProductPriceCriteriaDataExtractorInterface> $innerExtractors
     */
    public function __construct(iterable $innerExtractors)
    {
        $this->innerExtractors = $innerExtractors;
    }

    #[\Override]
    public function extractCriteriaData(ProductPriceCriteria $productPriceCriteria): array
    {
        foreach ($this->innerExtractors as $innerProvider) {
            if (!$innerProvider->isSupported($productPriceCriteria)) {
                continue;
            }

            return $innerProvider->extractCriteriaData($productPriceCriteria);
        }

        return [
            self::PRODUCT_IDS => [],
            self::UNIT_CODES => [],
            self::CURRENCIES => [],
        ];
    }

    #[\Override]
    public function isSupported(ProductPriceCriteria $productPriceCriteria): bool
    {
        foreach ($this->innerExtractors as $innerExtractor) {
            if ($innerExtractor->isSupported($productPriceCriteria)) {
                return true;
            }
        }

        return false;
    }
}
