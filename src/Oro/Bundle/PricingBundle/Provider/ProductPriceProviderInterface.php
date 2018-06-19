<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;

interface ProductPriceProviderInterface
{
    /**
     * @param ProductPriceScopeCriteriaInterface $scopeCriteria
     * @param array $productIds
     * @param string|null $currency
     * @return array
     */
    public function getPriceByPriceListIdAndProductIds(
        ProductPriceScopeCriteriaInterface $scopeCriteria,
        array $productIds,
        $currency = null
    );

    /**
     * @param array $productsPriceCriteria
     * @param ProductPriceScopeCriteriaInterface $scopeCriteria
     * @return mixed
     */
    public function getMatchedPrices(array $productsPriceCriteria, ProductPriceScopeCriteriaInterface $scopeCriteria);
}
