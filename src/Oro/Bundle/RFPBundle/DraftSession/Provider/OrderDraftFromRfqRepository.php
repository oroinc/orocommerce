<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\DraftSession\Provider;

use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\Provider\EntityDraftRepositoryInterface;

/**
 * Request entity does not have draft fields and should always use factory-based draft creation.
 */
class OrderDraftFromRfqRepository implements EntityDraftRepositoryInterface
{
    #[\Override]
    public function supports(string $entityClass): bool
    {
        return is_a($entityClass, Request::class, true);
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
