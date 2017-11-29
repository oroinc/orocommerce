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
}
