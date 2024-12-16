<?php

namespace Oro\Bundle\ShoppingListBundle\Event;

use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * ShoppingListEvent represents logic which was performed for shopping list
 */
class ShoppingListEvent extends Event
{
    public function __construct(private ShoppingList $shoppingList)
    {
    }

    public function getShoppingList(): ShoppingList
    {
        return $this->shoppingList;
    }

    public function setShoppingList(ShoppingList $shoppingList): self
    {
        $this->shoppingList = $shoppingList;
        return $this;
    }
}
