<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ProductBundle\Entity\Brand;

/**
 * Provides available brand status options.
 *
 * This provider returns the list of valid brand statuses (enabled/disabled)
 * for use in forms, filters, and other UI components.
 */
class BrandStatusProvider
{
    /**
     * @return array
     */
    public function getAvailableBrandStatuses()
    {
        return [
            'oro.product.brand.status.disabled' => Brand::STATUS_DISABLED,
            'oro.product.brand.status.enabled' => Brand::STATUS_ENABLED,
        ];
    }
}
