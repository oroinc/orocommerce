<?php

namespace Oro\Bundle\PricingBundle\Storage;

use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\ProductBundle\Entity\Product;

interface ProductPriceStorageInterface
{
    /**
     * @param ProductPriceScopeCriteriaInterface $scopeCriteria
     * @param array|Product[] $products
     * @param array|null $productUnitCodes
     * @param array|null $currencies
     * @return array
     */
    public function getPrices(
        ProductPriceScopeCriteriaInterface $scopeCriteria,
        array $products,
        array $productUnitCodes = null,
        array $currencies = null
    );

    /**
     * @param ProductPriceScopeCriteriaInterface $scopeCriteria
     * @return array|string[]
     */
    public function getSupportedCurrencies(ProductPriceScopeCriteriaInterface $scopeCriteria);
}
