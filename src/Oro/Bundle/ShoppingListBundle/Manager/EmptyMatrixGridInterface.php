<?php

namespace Oro\Bundle\ShoppingListBundle\Manager;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

interface EmptyMatrixGridInterface
{
    /**
     * @param ShoppingList $shoppingList
     * @param Product $product
     */
    public function addEmptyMatrix(ShoppingList $shoppingList, Product $product);

    /**
     * @param LineItem[] $lineItems
     * @return bool
     */
    public function isAddEmptyMatrixAllowed(array $lineItems): bool;

    /**
     * @param ShoppingList $shoppingList
     * @return bool
     */
    public function hasEmptyMatrix(ShoppingList $shoppingList): bool;
}
