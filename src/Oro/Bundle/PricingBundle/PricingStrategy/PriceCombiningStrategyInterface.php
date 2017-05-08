<?php

namespace Oro\Bundle\PricingBundle\PricingStrategy;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\ProductBundle\Entity\Product;

interface PriceCombiningStrategyInterface
{
    /**
     * @param CombinedPriceList $combinedPriceList
     * @param Product|null $product
     * @param int|null $startTimestamp
     */
    public function combinePrices(
        CombinedPriceList $combinedPriceList,
        Product $product = null,
        $startTimestamp = null
    );

}
