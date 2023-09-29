<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;

/**
 * Declares methods for product price providers returning product prices by the matching criteria.
 */
interface MatchedProductPriceProviderInterface
{
    /**
     * @param array<ProductPriceCriteria> $productsPriceCriteria
     * @param ProductPriceScopeCriteriaInterface $productPriceScopeCriteria
     *
     * @return array<string,ProductPriceInterface>
     *  [
     *      'product-price-criteria-identifier' => ProductPriceInterface,
     *      // ...
     *  ]
     */
    public function getMatchedProductPrices(
        array $productsPriceCriteria,
        ProductPriceScopeCriteriaInterface $productPriceScopeCriteria
    ): array;
}
