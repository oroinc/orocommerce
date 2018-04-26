<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ProductBundle\Entity\Brand;

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
