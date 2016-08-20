<?php

namespace Oro\Bundle\AccountBundle\Visibility\Cache;

use Oro\Bundle\ProductBundle\Entity\Product;

interface ProductCaseCacheBuilderInterface extends CacheBuilderInterface
{
    /**
     * @param Product $product
     * @return mixed
     */
    public function productCategoryChanged(Product $product);
}
