<?php

namespace Oro\Bundle\ShoppingListBundle\Event;

use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * Event executed after merging shopping list with current shopping list in GuestShoppingListMigrationManager
 */
class ShoppingListPostMergeEvent extends ShoppingListEvent
{
    public const string NAME = 'oro_shopping_list.post_merge';

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
