<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;

class ProductStatusProvider
{
    /**
     * @return array
     */
    public function getAvailableProductStatuses()
    {
        return [
            Product::STATUS_DISABLED => 'oro.product.status.disabled',
            Product::STATUS_ENABLED => 'oro.product.status.enabled'
        ];
    }
}
