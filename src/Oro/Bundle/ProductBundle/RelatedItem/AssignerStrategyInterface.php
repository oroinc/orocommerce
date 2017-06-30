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
     * @param Product[] $productsTo
     *
     * @throws \LogicException When functionality is disabled
     * @throws \InvalidArgumentException When user tries to add related product to itself
     * @throws \OverflowException When user tries to add more products than limit allows
     */
    public function addRelations(Product $productFrom, array $productsTo);

    /**
     * @param Product $productFrom
     * @param Product[] $productsTo
     */
    public function removeRelations(Product $productFrom, array $productsTo);
}
