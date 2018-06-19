<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;

/**
 * TODO: BB-14587 DO NOT return entities, as DB storage is only one of possible sources for pricing
 */
interface ProductPriceProviderInterface
{
    /**
     * @todo BB-14587 return array of DAO instead of array
     * @param ProductPriceScopeCriteriaInterface $scopeCriteria
     * @param array $productIds
     * @param string|null $currency
     * @return array
     */
    public function getPricesAsArrayByScopeCriteriaAndProductIds(
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
