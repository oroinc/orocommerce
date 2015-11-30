<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache;

use OroB2B\Bundle\ProductBundle\Entity\Product;

interface ProductCaseBuilderInterface extends CacheBuilderInterface
{
    /**
     * @param Product $product
     * @return mixed
     */
    public function productCategoryChanged(Product $product);
}
