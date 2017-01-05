<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;

class ProductVariantProvider
{
    /** @var ProductVariantAvailabilityProvider */
    protected $availabilityProvider;

    /**
     * @param ProductVariantAvailabilityProvider $availabilityProvider
     */
    public function __construct(ProductVariantAvailabilityProvider $availabilityProvider)
    {
        $this->availabilityProvider = $availabilityProvider;
    }

    /**
     * @param Product $configurableProduct
     * @return bool
     */
    public function hasProductAnyAvailableVariant(Product $configurableProduct)
    {
        $productVariants = $this->availabilityProvider->getSimpleProductsByVariantFields($configurableProduct);

        return (bool)count($productVariants);
    }
}
