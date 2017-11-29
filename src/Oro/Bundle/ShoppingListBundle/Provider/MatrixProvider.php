<?php

namespace Oro\Bundle\ShoppingListBundle\Provider;

use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class MatrixProvider
{
    /**
     * @param ShoppingList $shoppingList
     *
     * @return bool
     */
    public function hasEmptyMatrix(ShoppingList $shoppingList): bool
    {
        foreach ($shoppingList->getLineItems() as $lineItem) {
            if ($lineItem->getProduct()->isConfigurable()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param ShoppingList $shoppingList
     */
    public function removeEmptyMatricesFromShoppingList(ShoppingList $shoppingList)
    {
        foreach ($shoppingList->getLineItems() as $lineItem) {
            if (false === $lineItem->getProduct()->isConfigurable()) {
                continue;
            }

            $shoppingList->removeLineItem($lineItem);
        }
    }
}
