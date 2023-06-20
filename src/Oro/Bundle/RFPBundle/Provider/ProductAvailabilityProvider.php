<?php

namespace Oro\Bundle\RFPBundle\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Checks if a product is applicable for RFP.
 *
 * @deprecated since 5.1, will be removed in 6.0. Use {@see ProductRFPAvailabilityProvider} instead.
 */
class ProductAvailabilityProvider implements ProductAvailabilityProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function isProductApplicableForRFP(Product $product)
    {
        return !$product->isConfigurable() && !$product->isKit();
    }
}
