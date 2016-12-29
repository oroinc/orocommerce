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
            Product::TYPE_SIMPLE => 'oro.product.type.simple',
            Product::TYPE_CONFIGURABLE => 'oro.product.type.configurable'
        ];
    }
}
