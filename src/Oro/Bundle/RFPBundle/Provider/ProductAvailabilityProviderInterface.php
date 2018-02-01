<?php

namespace Oro\Bundle\RFPBundle\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;

interface ProductAvailabilityProviderInterface
{
    /**
     * @param Product $product
     * @return bool
     */
    public function isProductApplicableForRFP(Product $product);
}
