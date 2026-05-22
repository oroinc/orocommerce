<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Datagrid\DraftSession;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\BuildBeforeListenerInterface;

/**
 * Adds a datagrid property for deleting order line items in the order draft edit mode.
 */
final class OrderLineItemDraftDeleteDatagridListener implements BuildBeforeListenerInterface
{
    #[\Override]
    public function onBuildBefore(BuildBefore $event): void
    {
        $datagrid = $event->getDatagrid();
        $datagridConfig = $event->getConfig();

        $datagridConfig->offsetAddToArrayByPath('[properties]', [
            'oro_order_line_item_draft_delete' => [
                'type' => 'url',
                'route' => 'oro_order_line_item_draft_delete',
                'params' => ['orderLineItemId' => 'orderLineItemId'],
                'direct_params' => [
                    'orderId' => $datagrid->getParameters()->get('order_id'),
                    'orderDraftSessionUuid' => $datagrid->getParameters()->get('draft_session_uuid'),
                ],
            ],
        ]);
    }
}
