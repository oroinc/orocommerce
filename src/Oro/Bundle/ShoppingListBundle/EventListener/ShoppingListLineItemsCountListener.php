<?php

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * Calculates line items count.
 */
class ShoppingListLineItemsCountListener
{
    /** @var array */
    private $shoppingLists = [];

    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        $unitOfWork = $args->getEntityManager()
            ->getUnitOfWork();

        $entities = array_merge(
            $unitOfWork->getScheduledEntityInsertions(),
            $unitOfWork->getScheduledEntityDeletions()
        );

        foreach ($entities as $entity) {
            if ($entity instanceof LineItem) {
                $shoppingList = $entity->getShoppingList();
            } elseif ($entity instanceof ShoppingList) {
                $shoppingList = $entity;
            } else {
                continue;
            }

            $this->shoppingLists[spl_object_hash($shoppingList)] = $shoppingList;
        }
    }

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args): void
    {
        $this->shoppingLists = array_filter(
            $this->shoppingLists,
            static function (ShoppingList $shoppingList) {
                return $shoppingList->getId();
            }
        );

        if (!$this->shoppingLists) {
            return;
        }

        $repository = $args->getEntityManager()
            ->getRepository(ShoppingList::class);

        $data = $repository->getLineItemsCount(array_values($this->shoppingLists));
        foreach ($this->shoppingLists as $shoppingList) {
            $repository->setLineItemsCount($shoppingList, $data[$shoppingList->getId()] ?? 0);
        }

        $this->shoppingLists = [];
    }
}
