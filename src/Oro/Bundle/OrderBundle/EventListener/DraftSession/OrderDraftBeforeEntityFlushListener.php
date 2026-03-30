<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\EventListener\DraftSession;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * Removes the order draft when the original order is flushed to the database.
 */
class OrderDraftBeforeEntityFlushListener
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly OrderDraftManager $orderDraftManager
    ) {
    }

    public function onBeforeEntityFlush(AfterFormProcessEvent $event): void
    {
        /** @var Order|null $order */
        $order = $event->getData();
        if (!$order instanceof Order) {
            return;
        }

        $orderDraft = $this->orderDraftManager->getOrderDraft();
        if ($orderDraft !== null) {
            $this->doctrine->getManagerForClass(Order::class)->remove($orderDraft);
        }
    }
}
