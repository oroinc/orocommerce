<?php

namespace Oro\Bundle\ProductBundle\RelatedItem;

use Oro\Bundle\ProductBundle\Entity\Product;

/**
* Use this interface if you want to write your own code for getting related items (related/up-sell/cross-sell products)
*/
interface FinderStrategyInterface
{
    /**
     * Keep in mind, that this method works for frontend and backend (to fill related items grids).
     * Consider this while implementing.
     *
     * @param Product  $product
     * @param bool     $bidirectional
     * @param int|null $limit
     * @return int[]
     */
    public function findIds(Product $product, $bidirectional = false, $limit = null);
}
