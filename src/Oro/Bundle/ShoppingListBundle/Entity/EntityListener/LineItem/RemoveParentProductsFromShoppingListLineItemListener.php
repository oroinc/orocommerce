<?php

namespace Oro\Bundle\ShoppingListBundle\Entity\EntityListener\LineItem;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

class RemoveParentProductsFromShoppingListLineItemListener
{
    /**
     * @param LineItem           $lineItem
     * @param LifecycleEventArgs $event
     */
    public function prePersist(LineItem $lineItem, LifecycleEventArgs $event)
    {
        $shoppingList = $lineItem->getShoppingList();

        $configurableLineItems = $event->getEntityManager()->getRepository('OroShoppingListBundle:LineItem')->findBy([
            'shoppingList' => $shoppingList,
            'unit' => $lineItem->getProductUnit(),
            'product' => $lineItem->getProduct()->getParentProducts(),
        ]);

        foreach ($configurableLineItems as $item) {
            $shoppingList->removeLineItem($item);
        }
    }
}
