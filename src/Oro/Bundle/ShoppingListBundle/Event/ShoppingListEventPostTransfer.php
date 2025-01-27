<?php

namespace Oro\Bundle\ShoppingListBundle\Event;

use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is triggered after merging shopping lists from an anonymous user and a logged-in user.
 * It allows handling both the merged shopping list and the current shopping list of the logged-in user.
 */
class ShoppingListEventPostTransfer extends Event
{
    public const NAME = 'oro_shopping_list.post_transfer';

    public function __construct(private ShoppingList $shoppingList, private ShoppingList $currentShoppingList)
    {
    }

    public function getShoppingList(): ShoppingList
    {
        return $this->shoppingList;
    }

    public function getCurrentShoppingList(): ShoppingList
    {
        return $this->currentShoppingList;
    }
}
