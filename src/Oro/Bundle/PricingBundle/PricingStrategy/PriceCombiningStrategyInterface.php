<?php

namespace Oro\Bundle\PricingBundle\PricingStrategy;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Interface for price combining strategy.
 */
interface PriceCombiningStrategyInterface
{
    /**
     * @param CombinedPriceList $combinedPriceList
     * @param array|Product[] $products
     */
    public function combinePrices(
        CombinedPriceList $combinedPriceList,
        array $products = []
    ): void;
}
