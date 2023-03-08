<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Provides a list of available in product types.
 */
class ProductTypeProvider
{
    /**
     * @return array
     */
    public function getAvailableProductTypes()
    {
        return [
            'oro.product.type.simple' => Product::TYPE_SIMPLE,
            'oro.product.type.configurable' => Product::TYPE_CONFIGURABLE,
            'oro.product.type.kit' => Product::TYPE_KIT,
        ];
    }
}
