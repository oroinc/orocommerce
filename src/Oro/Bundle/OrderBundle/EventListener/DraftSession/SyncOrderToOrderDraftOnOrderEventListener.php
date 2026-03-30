<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\EventListener\DraftSession;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Event\OrderEvent;

/**
 * Synchronizes order to order draft on order event.
 */
class SyncOrderToOrderDraftOnOrderEventListener
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly OrderDraftManager $orderDraftManager
    ) {
    }

    public function onOrderEvent(OrderEvent $event): void
    {
        $draftSessionUuid = $this->orderDraftManager->getDraftSessionUuid();
        if (!$draftSessionUuid) {
            return;
        }

        /** @var Order|null $order */
        $order = $event->getOrder();
        if (!$order instanceof Order) {
            return;
        }

        if (!$event->getForm()->isSubmitted()) {
            // No need to synchronize back when not submitted.
            return;
        }

        $entityManager = $this->doctrine->getManagerForClass(Order::class);
        // Clears the entity manager to ensure that only order draft changes are flushed to the database.
        // This is necessary because this listener is executed during OrderEvent processing and there
        // might be other changes in the entity manager that are not expected to be flushed
        // to the database at this moment.
        $entityManager->clear();

        // Retrieves the order draft from DB after the entity manager is cleared.
        $orderDraft = $this->orderDraftManager->findOrderDraft($draftSessionUuid);

        if ($orderDraft) {
            $this->orderDraftManager->synchronizeEntityToDraft($order, $orderDraft);
            $entityManager->flush();
        }
    }
}
