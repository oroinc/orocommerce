<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\DataExtractor;

use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaDataExtractor\ProductPriceCriteriaDataExtractorInterface;
use Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\ProductKitItemPriceCriteria;

/**
 * Extracts data from {@see ProductKitItemPriceCriteria}.
 */
class ProductKitItemPriceCriteriaDataExtractor implements ProductPriceCriteriaDataExtractorInterface
{
    #[\Override]
    public function extractCriteriaData(
        ProductPriceCriteria|ProductKitItemPriceCriteria $productPriceCriteria
    ): array {
        if (!$this->isSupported($productPriceCriteria)) {
            return [
                self::PRODUCT_IDS => [],
                self::UNIT_CODES => [],
                self::CURRENCIES => [],
            ];
        }

        return [
            self::PRODUCT_IDS => [$productPriceCriteria->getProduct()->getId()],
            self::UNIT_CODES => [$productPriceCriteria->getProductUnit()->getCode()],
            self::CURRENCIES => [$productPriceCriteria->getCurrency()],
        ];
    }

    #[\Override]
    public function isSupported(ProductPriceCriteria|ProductKitItemPriceCriteria $productPriceCriteria): bool
    {
        return $productPriceCriteria instanceof ProductKitItemPriceCriteria;
    }
}
