<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Datagrid\DraftSession;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;

/**
 * Adds draft session parameters to the datagrid parameters if they are not already present.
 */
class AddOrderDraftSessionParametersDatagridListener
{
    public function __construct(
        private readonly OrderDraftManager $orderDraftManager,
    ) {
    }

    public function onBuildBefore(BuildBefore $event): void
    {
        $datagrid = $event->getDatagrid();
        $parameterBag = $datagrid->getParameters();
        $draftSessionUuid = filter_var(
            $parameterBag->get('draft_session_uuid'),
            FILTER_DEFAULT,
            ['flags' => FILTER_REQUIRE_SCALAR]
        );

        if ($draftSessionUuid && !$parameterBag->get('order_draft_id')) {
            $orderDraft = $this->orderDraftManager->findOrderDraft($draftSessionUuid);
            if ($orderDraft !== null) {
                $parameterBag->set('order_draft_id', $orderDraft->getId());

                $datagridConfig = $datagrid->getConfig();
                $datagridConfig->offsetAddToArrayByPath('[options][urlParams]', [
                    'order_draft_id' => $orderDraft->getId()
                ]);
            }
        }
    }
}
