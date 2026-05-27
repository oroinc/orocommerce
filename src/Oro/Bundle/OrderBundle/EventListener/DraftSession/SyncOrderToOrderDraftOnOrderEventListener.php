<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\EventListener\DraftSession;

use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Event\OrderEvent;

/**
 * Synchronizes order to order draft on order event.
 */
final class SyncOrderToOrderDraftOnOrderEventListener
{
    public function __construct(
        private readonly OrderDraftManager $orderDraftManager
    ) {
    }

    public function onOrderEvent(OrderEvent $event): void
    {
        $form = $event->getForm();
        if (!$form->getConfig()->getOption('draft_session_sync')) {
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

        if (!$this->orderDraftManager->hasEntityDraft($order)) {
            return;
        }

        $this->orderDraftManager->saveToEntityDraft($order);
    }
}
