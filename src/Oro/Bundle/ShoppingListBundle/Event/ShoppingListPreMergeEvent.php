<?php

namespace Oro\Bundle\ShoppingListBundle\Event;

use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * Event executed before assigning shopping list with current shopping list in GuestShoppingListMigrationManager
 */
class ShoppingListPreMergeEvent extends ShoppingListEvent
{
    public const string NAME = 'oro_shopping_list.pre_merge';

    public function __construct(
        private ShoppingList $currentShoppingList,
        ShoppingList $shoppingList
    ) {
        parent::__construct($shoppingList);
    }

    public function getCurrentShoppingList(): ShoppingList
    {
        return $this->currentShoppingList;
    }

    public function setCurrentShoppingList(ShoppingList $currentShoppingList): self
    {
        $this->currentShoppingList = $currentShoppingList;
        return $this;
    }
}
