<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\DataExtractor;

use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaDataExtractor\ProductPriceCriteriaDataExtractorInterface;
use Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\ProductKitPriceCriteria;

/**
 * Extracts data from {@see ProductKitPriceCriteria}
 */
class ProductKitPriceCriteriaDataExtractor implements ProductPriceCriteriaDataExtractorInterface
{
    private ProductPriceCriteriaDataExtractorInterface $productPriceCriteriaDataExtractor;

    public function __construct(ProductPriceCriteriaDataExtractorInterface $productPriceCriteriaDataExtractor)
    {
        $this->productPriceCriteriaDataExtractor = $productPriceCriteriaDataExtractor;
    }

    #[\Override]
    public function extractCriteriaData(ProductPriceCriteria|ProductKitPriceCriteria $productPriceCriteria): array
    {
        if (!$this->isSupported($productPriceCriteria)) {
            return [
                self::PRODUCT_IDS => [],
                self::UNIT_CODES => [],
                self::CURRENCIES => [],
            ];
        }

        $productIds = [[$productPriceCriteria->getProduct()->getId()]];
        $unitCodes = [[$productPriceCriteria->getProductUnit()->getCode()]];
        $currencies = [[$productPriceCriteria->getCurrency()]];

        foreach ($productPriceCriteria->getKitItemsProductsPriceCriteria() as $kitItemProductPriceCriterion) {
            $criteriaData = $this->productPriceCriteriaDataExtractor
                ->extractCriteriaData($kitItemProductPriceCriterion);
            $productIds[] = $criteriaData[self::PRODUCT_IDS];
            $unitCodes[] = $criteriaData[self::UNIT_CODES];
            $currencies[] = $criteriaData[self::CURRENCIES];
        }

        return [
            self::PRODUCT_IDS => array_values(array_unique(array_merge(...$productIds))),
            self::UNIT_CODES => array_values(array_unique(array_merge(...$unitCodes))),
            self::CURRENCIES => array_values(array_unique(array_merge(...$currencies))),
        ];
    }

    #[\Override]
    public function isSupported(ProductPriceCriteria|ProductKitPriceCriteria $productPriceCriteria): bool
    {
        return $productPriceCriteria instanceof ProductKitPriceCriteria;
    }
}
