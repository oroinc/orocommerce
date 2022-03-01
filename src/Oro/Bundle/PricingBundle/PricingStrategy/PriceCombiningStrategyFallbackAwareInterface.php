<?php

namespace Oro\Bundle\PricingBundle\PricingStrategy;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Provider\PriceListSequenceMember;

/**
 * Interface for strategies that may use fallback CPLs for CPL combining process.
 * @deprecated Will be removed in 5.1. Fallback CPL now selected among all calculated CPLs
 *             without a need in owning entity like customer group
 */
interface PriceCombiningStrategyFallbackAwareInterface
{
    /**
     * @param CombinedPriceList $combinedPriceList
     * @param array|PriceListSequenceMember[] $priceLists
     * @param CombinedPriceList $fallbackLevelCpl
     * @param null|int $startTimestamp
     */
    public function combinePricesUsingPrecalculatedFallback(
        CombinedPriceList $combinedPriceList,
        array $priceLists,
        CombinedPriceList $fallbackLevelCpl,
        $startTimestamp = null
    );

    /**
     * Merge prices from a fallback CPL on top of prices in the combined price list.
     * Merge may be done for a limited set of products.
     */
    public function processCombinedPriceListRelation(
        CombinedPriceList $combinedPriceList,
        CombinedPriceList $relatedCombinedPriceList
    );
}
