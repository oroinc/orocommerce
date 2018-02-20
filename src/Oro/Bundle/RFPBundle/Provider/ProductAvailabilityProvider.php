<?php

namespace Oro\Bundle\RFPBundle\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;

class ProductAvailabilityProvider implements ProductAvailabilityProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function isProductApplicableForRFP(Product $product)
    {
        return $product->getType() !== Product::TYPE_CONFIGURABLE;
    }
}
