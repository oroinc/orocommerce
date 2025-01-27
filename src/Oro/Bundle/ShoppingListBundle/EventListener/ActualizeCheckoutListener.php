<?php

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Oro\Bundle\CheckoutBundle\Event\CheckoutActualizeEvent;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * Set checkout customer notes by shopping list on checkout actualization.
 */
class ActualizeCheckoutListener
{
    public function onActualize(CheckoutActualizeEvent $event): void
    {
        $sourceCriteria = $event->getSourceCriteria();
        $shoppingList = $sourceCriteria['shoppingList'] ?? null;
        if ($shoppingList instanceof ShoppingList && $shoppingList->getNotes()) {
            $event->getCheckout()->setCustomerNotes($shoppingList->getNotes());
        }
    }
}
