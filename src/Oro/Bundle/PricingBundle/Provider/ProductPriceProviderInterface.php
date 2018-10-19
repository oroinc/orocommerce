<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\ProductBundle\Entity\Product;

interface ProductPriceProviderInterface
{
    /**
     * @param ProductPriceScopeCriteriaInterface $scopeCriteria
     * @param array|Product[] $products
     * @param string|null $currency
     * @param string|null $unitCode
     * @return array
     */
    public function getPricesByScopeCriteriaAndProductIds(
        ProductPriceScopeCriteriaInterface $scopeCriteria,
        array $products,
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
