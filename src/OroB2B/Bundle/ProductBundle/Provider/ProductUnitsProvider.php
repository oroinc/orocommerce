<?php

namespace OroB2B\Bundle\ProductBundle\Provider;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class ProductStatusProvider
{
    /**
     * @return array
     */
    public function getAvailableProductStatuses()
    {
        return [
            Product::STATUS_DISABLED => 'orob2b.product.status.disabled',
            Product::STATUS_ENABLED => 'orob2b.product.status.enabled'
        ];
    }
}
