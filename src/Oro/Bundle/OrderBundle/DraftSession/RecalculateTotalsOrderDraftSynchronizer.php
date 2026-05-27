<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\DraftSession;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\Synchronizer\EntityDraftSynchronizerInterface;

/**
 * Recalculates the totals for the order when synchronized from draft.
 */
class RecalculateTotalsOrderDraftSynchronizer implements EntityDraftSynchronizerInterface
{
    public function __construct(
        private readonly TotalHelper $totalHelper,
    ) {
    }

    #[\Override]
    public function supports(string $entityClass): bool
    {
        return $entityClass === Order::class;
    }

    #[\Override]
    public function synchronizeFromDraft(EntityDraftAwareInterface $draft, EntityDraftAwareInterface $entity): void
    {
        assert($entity instanceof Order);

        $this->totalHelper->fill($entity);
    }

    #[\Override]
    public function synchronizeToDraft(EntityDraftAwareInterface $entity, EntityDraftAwareInterface $draft): void
    {
        // Totals are only recalculated when syncing from draft to order.
    }
}
