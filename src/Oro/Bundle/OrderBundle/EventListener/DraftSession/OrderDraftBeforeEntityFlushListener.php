<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\EventListener\DraftSession;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;

/**
 * Removes the order draft when the original order is flushed to the database.
 *
 * @bc-layer This listener is retained for BC reasons. It is replaced
 * by {@see DeleteOrderDraftOnAfterEntityFlushListener}.
 */
class OrderDraftBeforeEntityFlushListener
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly OrderDraftManager $orderDraftManager
    ) {
    }

    /**
     * @bc-layer This method is retained for BC reasons.
     */
    public function onBeforeEntityFlush(AfterFormProcessEvent $event): void
    {
    }
}
