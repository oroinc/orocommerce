<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\EventListener\DraftSession;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\Event\TotalCalculateBeforeEvent;

/**
 * Synchronizes order draft to order before totals are calculated.
 */
class OrderDraftAwareTotalCalculateListener
{
    public function __construct(
        private readonly OrderDraftManager $orderDraftManager,
        private readonly FrontendHelper $frontendHelper,
    ) {
    }

    public function onBeforeTotalCalculate(TotalCalculateBeforeEvent $event): void
    {
        $entity = $event->getEntity();
        if (!$entity instanceof Order) {
            return;
        }

        if ($this->frontendHelper->isFrontendRequest()) {
            return;
        }

        $orderDraft = $this->orderDraftManager->getOrderDraft();
        if (!$orderDraft) {
            return;
        }

        $this->orderDraftManager->synchronizeEntityFromDraft($orderDraft, $entity);
    }
}
