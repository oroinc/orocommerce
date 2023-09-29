<?php

namespace Oro\Bundle\PricingBundle\Provider\PriceByMatchingCriteria;

use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceCollectionDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;

/**
 * Interface for the providers of a product price by the specified matching product price criteria.
 */
interface ProductPriceByMatchingCriteriaProviderInterface
{
    /**
     * @param ProductPriceCriteria $productPriceCriteria
     * @param ProductPriceCollectionDTO $productPriceCollection
     *
     * @return ProductPriceInterface|null Product price object or null when no matching price found
     *  in $productPriceCollection.
     */
    public function getProductPriceMatchingCriteria(
        ProductPriceCriteria $productPriceCriteria,
        ProductPriceCollectionDTO $productPriceCollection
    ): ?ProductPriceInterface;

    public function isSupported(
        ProductPriceCriteria $productPriceCriteria,
        ProductPriceCollectionDTO $productPriceCollection
    ): bool;
}
