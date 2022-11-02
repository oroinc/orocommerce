<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Declares set of methods to get prices taking into account given criteria
 * Get supported currencies according to given criteria
 */
interface ProductPriceProviderInterface
{
    /**
     * @param ProductPriceScopeCriteriaInterface $scopeCriteria
     * @param int[]|Product[]                    $products
     * @param string[]                           $currencies
     * @param string|null                        $unitCode
     *
     * @return array [product id => [ProductPriceInterface, ...], ...]
     */
    public function getPricesByScopeCriteriaAndProducts(
        ProductPriceScopeCriteriaInterface $scopeCriteria,
        array $products,
        array $currencies,
        string $unitCode = null
    ): array;

    /**
     * @param ProductPriceCriteria[]             $productPriceCriteria
     * @param ProductPriceScopeCriteriaInterface $scopeCriteria
     *
     * @return array [product id => Oro\Bundle\CurrencyBundle\Entity\Price|null, ...]
     */
    public function getMatchedPrices(
        array $productPriceCriteria,
        ProductPriceScopeCriteriaInterface $scopeCriteria
    ): array;

    /**
     * @param ProductPriceScopeCriteriaInterface $scopeCriteria
     *
     * @return string[]
     */
    public function getSupportedCurrencies(ProductPriceScopeCriteriaInterface $scopeCriteria): array;
}
