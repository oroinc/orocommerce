<?php

namespace Oro\Bundle\ProductBundle\RelatedItem;

use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Use this interface if you want to write your own code for getting related products
 * You can see example of usage in \Oro\Bundle\ProductBundle\RelatedProducts\DatabaseStrategy
 */
interface StrategyInterface
{
    /**
     * @param Product $product
     * @param array|null $context can be used to pass additional data
     * @return Product[]
     */
    public function findRelatedProducts(Product $product, array $context = []);
}
