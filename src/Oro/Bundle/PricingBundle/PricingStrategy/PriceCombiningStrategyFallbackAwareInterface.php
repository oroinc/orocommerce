<?php

namespace Oro\Bundle\PricingBundle\PricingStrategy;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Provider\PriceListSequenceMember;

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
     * @param CombinedPriceList $combinedPriceList
     * @param CombinedPriceList $relatedCombinedPriceList
     */
    public function processCombinedPriceListRelation(
        CombinedPriceList $combinedPriceList,
        CombinedPriceList $relatedCombinedPriceList
    );
}
