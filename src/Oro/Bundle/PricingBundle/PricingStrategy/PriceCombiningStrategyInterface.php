<?php

namespace Oro\Bundle\PricingBundle\PricingStrategy;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\ProductBundle\Entity\Product;

interface PriceCombiningStrategyInterface
{
    /**
     * @param CombinedPriceList $combinedPriceList
     * @param array|Product[] $products
     * @param int|null $startTimestamp
     */
    public function combinePrices(
        CombinedPriceList $combinedPriceList,
        array $products = [],
        $startTimestamp = null
    );
}
