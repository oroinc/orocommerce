<?php

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListStorage;

/**
 * Flushes default shopping list id cache during shopping list deletion
 */
class FlushDefaultShoppingListCache
{
    private CurrentShoppingListStorage $currentShoppingListStorage;

    public function __construct(CurrentShoppingListStorage $currentShoppingListStorage)
    {
        $this->currentShoppingListStorage = $currentShoppingListStorage;
    }

    public function postRemove(ShoppingList $shoppingList)
    {
        $customerUser = $shoppingList->getCustomerUser();

        if ($customerUser) {
            $this->currentShoppingListStorage->set($customerUser->getId(), null);
        }
    }
}
