<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Datagrid\DraftSession;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;

/**
 * Adds draft session parameters to the datagrid parameters if they are not already present.
 *
 * @bc-layer This listener is retained for BC reasons. It won't have any replacement.
 */
class AddOrderDraftSessionParametersDatagridListener
{
    public function __construct(
        private readonly OrderDraftManager $orderDraftManager,
    ) {
    }

    /**
     * @bc-layer This method is retained for BC reasons. It won't have any replacement.
     */
    public function onBuildBefore(BuildBefore $event): void
    {
    }
}
