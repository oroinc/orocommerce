<?php

namespace Oro\Bundle\RFPBundle\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Interface for providers checking if a product is applicable for RFP.
 *
 * @deprecated since 5.1, will be removed in 6.0. Use {@see ProductRFPAvailabilityProvider} instead.
 */
interface ProductAvailabilityProviderInterface
{
    /**
     * @param Product $product
     * @return bool
     */
    public function isProductApplicableForRFP(Product $product);
}
