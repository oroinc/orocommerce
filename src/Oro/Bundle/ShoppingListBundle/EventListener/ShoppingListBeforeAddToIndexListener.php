<?php

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\SearchBundle\Event\BeforeEntityAddToIndexEvent;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * Before shopping list send to index, check if it's guest one and remove it.
 */
class ShoppingListBeforeAddToIndexListener
{
    public function checkEntityNeedIndex(BeforeEntityAddToIndexEvent $event): void
    {
        if ($this->isGuest($event->getEntity())) {
            $event->setEntity(null);
            $event->stopPropagation();
        }
    }

    private function isGuest(object $entity): bool
    {
        return $entity instanceof ShoppingList
            && $entity->getId() === null
            && $entity->getVisitor() instanceof CustomerVisitor;
    }
}
