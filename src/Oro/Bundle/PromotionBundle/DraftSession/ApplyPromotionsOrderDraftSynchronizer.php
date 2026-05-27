<?php

declare(strict_types=1);

namespace Oro\Bundle\PromotionBundle\DraftSession;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Manager\AppliedPromotionManager;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\Synchronizer\EntityDraftSynchronizerInterface;

/**
 * Applies promotions to the order when synchronized from draft.
 */
class ApplyPromotionsOrderDraftSynchronizer implements EntityDraftSynchronizerInterface
{
    public function __construct(
        private readonly AppliedPromotionManager $appliedPromotionManager,
    ) {
    }

    #[\Override]
    public function supports(string $entityClass): bool
    {
        return $entityClass === Order::class;
    }

    #[\Override]
    public function synchronizeFromDraft(
        EntityDraftAwareInterface $draft,
        EntityDraftAwareInterface $entity,
    ): void {
        assert($entity instanceof Order);

        $this->appliedPromotionManager->createAppliedPromotions($entity);
    }

    #[\Override]
    public function synchronizeToDraft(
        EntityDraftAwareInterface $entity,
        EntityDraftAwareInterface $draft,
    ): void {
        // Promotions are only applied when syncing from draft to order.
    }
}
