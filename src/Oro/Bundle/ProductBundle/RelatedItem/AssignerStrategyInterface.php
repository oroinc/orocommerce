<?php

namespace Oro\Bundle\ProductBundle\RelatedItem;

use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * This interface covers methods to add and remove product relations (eg. related/up-sell/cross-sell products)
 */
interface AssignerStrategyInterface
{
    /**
     * @param Product $productFrom
     * @param Product $productTo
     *
     * @throws \LogicException When functionality is disabled
     * @throws \InvalidArgumentException When user tries to add related product to itself
     * @throws \OverflowException When user tries to add more products than limit allows
     */
    public function addRelation(Product $productFrom, Product $productTo);

    /**
     * @param Product $productFrom
     * @param Product $productTo
     */
    public function removeRelation(Product $productFrom, Product $productTo);
}
