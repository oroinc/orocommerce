<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Datagrid\DraftSession;

use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Event\OrmResultBefore;
use Oro\Component\DraftSession\Manager\DraftSessionOrmFilterManager;

/**
 * Disables the order_draft ORM filter for order line items datagrid to allow view and edit for draft order line items.
 * The filter is disabled before the ORM queries are executed and re-enabled after.
 */
class OrderLineItemsDraftSessionFilterListener
{
    /**
     * Stores the original state of the order draft ORM filter before it is disabled.
     */
    private bool $isOrderDraftOrmFilterEnabled = true;

    public function __construct(
        private readonly DraftSessionOrmFilterManager $draftSessionOrmFilterManager
    ) {
    }

    public function onResultBefore(OrmResultBefore $event): void
    {
        $this->isOrderDraftOrmFilterEnabled = $this->draftSessionOrmFilterManager->isEnabled();

        // Disable the draft session filter right before executing ORM queries.
        $this->draftSessionOrmFilterManager->disable();
    }

    public function onResultAfter(OrmResultAfter $event): void
    {
        // Restores the original state of the draft session filter after executing ORM queries.
        if ($this->isOrderDraftOrmFilterEnabled) {
            $this->draftSessionOrmFilterManager->enable();
        }
    }
}
