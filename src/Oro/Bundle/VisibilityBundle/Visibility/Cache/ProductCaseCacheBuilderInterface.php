<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache;

use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Visibility cache builder for products.
 */
interface ProductCaseCacheBuilderInterface extends CacheBuilderInterface
{
    /**
     * @param Product $product
     * @param bool $scheduleReindex
     *
     * @return mixed
     */
    public function productCategoryChanged(Product $product, bool $scheduleReindex);
}
