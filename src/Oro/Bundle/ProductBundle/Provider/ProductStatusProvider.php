<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Provides available product status options.
 *
 * This provider returns the list of valid product statuses (enabled/disabled) for use in forms, filters,
 * and other UI components.
 */
class ProductStatusProvider
{
    /**
     * @return array
     */
    public function getAvailableProductStatuses()
    {
        return [
            'oro.product.status.disabled' => Product::STATUS_DISABLED,
            'oro.product.status.enabled' => Product::STATUS_ENABLED,
        ];
    }
}
