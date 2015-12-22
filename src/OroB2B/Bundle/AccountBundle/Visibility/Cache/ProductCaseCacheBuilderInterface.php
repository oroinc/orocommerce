<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache;

use OroB2B\Bundle\ProductBundle\Entity\Product;

interface ProductCaseCacheBuilderInterface extends CacheBuilderInterface
{
    /**
     * @param Product $product
     * @return mixed
     */
    public function productCategoryChanged(Product $product);
}
