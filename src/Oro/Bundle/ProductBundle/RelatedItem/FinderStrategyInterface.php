<?php

namespace Oro\Bundle\ProductBundle\RelatedItem;

use Oro\Bundle\ProductBundle\Entity\Product;

/**
* Use this interface if you want to write your own code for getting related items (related/up-sell/cross-sell products)
*/
interface FinderStrategyInterface
{
    /**
     * @param Product $product
     * @return Product[]
     */
    public function find(Product $product);
}
