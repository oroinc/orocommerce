<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Entity\EntityListener\LineItem;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

/**
 * Removes the line items:
 * - when variants are added, the parent configurable line item should be removed.
 * - when a parent configurable is added, any existing variant line items should be removed.
 */
class RemoveParentAndVariantLineItemsFromShoppingListListener
{
    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();
        $repo = $em->getRepository(LineItem::class);

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if (!$entity instanceof LineItem || !$entity->getParentProduct()) {
                continue;
            }

            // Remove parent configurable line item if variants are added
            foreach ($repo->getParentItemsByParentProduct($entity) as $lineItem) {
                $lineItem->removeFromAssociatedList();
                $em->remove($lineItem);
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof LineItem) {
                continue;
            }

            // Clear variant line items if a parent configurable is added
            if ($entity->getProduct()?->isConfigurable()) {
                foreach ($repo->getVariantsItemsByParentProduct($entity) as $lineItem) {
                    $lineItem->removeFromAssociatedList();
                    $em->remove($lineItem);
                }
            }

            // Remove parent configurable line item if variants are added
            if ($entity->getParentProduct()) {
                foreach ($repo->getParentItemsByParentProduct($entity) as $lineItem) {
                    $lineItem->removeFromAssociatedList();
                    $em->remove($lineItem);
                }
            }
        }
    }
}
