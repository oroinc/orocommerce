<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\EventListener\DraftSession;

use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * Deletes the current order draft after an Order entity is flushed by the form handler.
 */
final class DeleteOrderDraftOnAfterEntityFlushListener
{
    public function __construct(
        private readonly OrderDraftManager $orderDraftManager
    ) {
    }

    public function onAfterEntityFlush(AfterFormProcessEvent $event): void
    {
        /** @var Order|null $order */
        $order = $event->getData();
        if (!$order instanceof Order) {
            return;
        }

        $this->orderDraftManager->deleteEntityDraft($order);
    }
}
