<?php

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Oro\Bundle\CheckoutBundle\Event\CheckoutSourceEntityClearEvent;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * Handles the cleanup of Shopping List-specific fields when the Checkout source entity is cleared.
 */
class ShoppingListCheckoutSourceEntityClearListener
{
    public function onCheckoutSourceEntityClear(CheckoutSourceEntityClearEvent $event): void
    {
        $checkoutSourceEntity = $event->getCheckoutSourceEntity();
        if ($checkoutSourceEntity instanceof ShoppingList) {
            $checkoutSourceEntity->setNotes(null);
        }
    }
}
