<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;

interface ProductsProviderInterface
{
    /**
     * @return Product[]
     */
    public function getProducts();
}
