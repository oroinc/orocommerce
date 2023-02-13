<?php

namespace Oro\Bundle\ShoppingListBundle\Entity\EntityListener\LineItem;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

/**
 * Removes the line item with the product specified as parent product of the currently persisted line item.
 */
class RemoveParentProductsFromShoppingListLineItemListener
{
    public function prePersist(LineItem $lineItem, LifecycleEventArgs $event): void
    {
        if (!$lineItem->getParentProduct()) {
            return;
        }

        $shoppingList = $lineItem->getShoppingList();

        $parentLineItems = $event
            ->getObjectManager()
            ->getRepository(LineItem::class)
            ->findBy([
                'shoppingList' => $shoppingList->getId(),
                'unit' => $lineItem->getProductUnitCode(),
                'product' => $lineItem->getParentProduct()->getId(),
            ]);

        foreach ($parentLineItems as $item) {
            $shoppingList->removeLineItem($item);
        }
    }
}
