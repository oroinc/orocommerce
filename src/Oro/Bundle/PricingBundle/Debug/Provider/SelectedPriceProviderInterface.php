<?php

namespace Oro\Bundle\PricingBundle\Debug\Provider;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Interface for price merge strategy based info providers.
 * Provider should return ids of used prices chosen by the strategy.
 */
interface SelectedPriceProviderInterface
{
    /**
     * @param array|CombinedPriceListToPriceList[] $priceListRelations
     * @param Product $product
     * @return array
     */
    public function getSelectedPricesIds(array $priceListRelations, Product $product): array;
}
