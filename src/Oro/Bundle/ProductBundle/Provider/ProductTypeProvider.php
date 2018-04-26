<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;

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
        ];
    }
}
