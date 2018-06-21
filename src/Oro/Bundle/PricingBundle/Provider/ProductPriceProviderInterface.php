<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;

interface ProductPriceProviderInterface
{
    /**
     * @todo BB-14587 return array of DAO instead of array? Will require refactoring of all clients
     *
     * @param ProductPriceScopeCriteriaInterface $scopeCriteria
     * @param array $productIds
     * @param string|null $currency
     * @param string|null $unitCode
     * @return array
     */
    public function getPricesByScopeCriteriaAndProductIds(
        ProductPriceScopeCriteriaInterface $scopeCriteria,
        array $productIds,
        $currency = null,
        $unitCode = null
    );

    /**
     * @param ProductPriceCriteria[] $productPriceCriterias
     * @param ProductPriceScopeCriteriaInterface $scopeCriteria
     * @return mixed
     */
    public function getMatchedPrices(array $productPriceCriterias, ProductPriceScopeCriteriaInterface $scopeCriteria);

    /**
     * @param ProductPriceScopeCriteriaInterface $scopeCriteria
     * @return array|string[]
     */
    public function getSupportedCurrencies(ProductPriceScopeCriteriaInterface $scopeCriteria);
}
