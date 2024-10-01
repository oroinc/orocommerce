<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaDataExtractor;

use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;

/**
 * Extracts data from {@see ProductPriceCriteria}.
 */
class SimpleProductPriceCriteriaDataExtractor implements ProductPriceCriteriaDataExtractorInterface
{
    #[\Override]
    public function extractCriteriaData(ProductPriceCriteria $productPriceCriteria): array
    {
        return [
            self::PRODUCT_IDS => [$productPriceCriteria->getProduct()->getId()],
            self::UNIT_CODES => [$productPriceCriteria->getProductUnit()->getCode()],
            self::CURRENCIES => [$productPriceCriteria->getCurrency()],
        ];
    }

    #[\Override]
    public function isSupported(ProductPriceCriteria $productPriceCriteria): bool
    {
        return true;
    }
}
