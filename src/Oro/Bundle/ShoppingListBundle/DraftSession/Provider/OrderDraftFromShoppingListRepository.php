<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\DraftSession\Provider;

use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\Provider\EntityDraftRepositoryInterface;

/**
 * ShoppingList entity does not have draft fields and should always use factory-based draft creation.
 */
class OrderDraftFromShoppingListRepository implements EntityDraftRepositoryInterface
{
    #[\Override]
    public function supports(string $entityClass): bool
    {
        return is_a($entityClass, ShoppingList::class, true);
    }

    #[\Override]
    public function hasEntityDraft(
        EntityDraftAwareInterface $entityOrDraft,
        string $draftSessionUuid,
    ): bool {
        return false;
    }

    #[\Override]
    public function findEntityDraft(
        EntityDraftAwareInterface $entityOrDraft,
        string $draftSessionUuid,
    ): ?EntityDraftAwareInterface {
        return null;
    }
}
