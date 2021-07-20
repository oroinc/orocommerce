<?php

namespace Oro\Bundle\ShoppingListBundle\Entity\EntityListener\LineItem;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

class RemoveParentProductsFromShoppingListLineItemListener
{
    public function prePersist(LineItem $lineItem, LifecycleEventArgs $event)
    {
        $shoppingList = $lineItem->getShoppingList();

        $parentLineItems = $event->getEntityManager()->getRepository('OroShoppingListBundle:LineItem')->findBy([
            'shoppingList' => $shoppingList,
            'unit' => $lineItem->getProductUnit(),
            'product' => $lineItem->getParentProduct(),
        ]);

        foreach ($parentLineItems as $item) {
            $shoppingList->removeLineItem($item);
        }
    }
}
