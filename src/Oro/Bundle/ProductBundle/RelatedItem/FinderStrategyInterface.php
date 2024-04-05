<?php

namespace Oro\Bundle\ProductBundle\RelatedItem;

use Oro\Bundle\ProductBundle\Entity\Product;

/**
* Represents a service that is used to get IDs of related items (related/up-sell/cross-sell products).
*/
interface FinderStrategyInterface
{
    /**
     * Gets IDs of related items.
     * Keep in mind, that this method works for frontend and backend (to fill related items grids).
     * Consider this while implementing.
     *
     * @param Product  $product
     *
     * @return int[]
     */
    public function findIds(Product $product): array;
}
