<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\EventListener\DataGrid;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\BuildBeforeListenerInterface;
use Oro\Bundle\PaymentBundle\Filter\PaymentStatusFilter;
use Oro\Bundle\PaymentBundle\Form\Type\Filter\PaymentStatusFilterType;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\SegmentBundle\Entity\Segment;

/**
 * Listens to datagrid build events and configures the payment status filter for report datagrid.
 * It sets the target entity for the payment status filter to include custom payments statuses available
 * for specific entity.
 *
 * {@see PaymentStatusFilter} and {@see PaymentStatusFilterType}
 */
final class PaymentStatusFilterDatagridListener implements BuildBeforeListenerInterface
{
    #[\Override]
    public function onBuildBefore(BuildBefore $event): void
    {
        $datagrid = $event->getDatagrid();
        $datagridName = $datagrid->getName();
        if (
            !str_contains($datagridName, Report::GRID_PREFIX) &&
            !str_contains($datagridName, Segment::GRID_PREFIX)
        ) {
            return;
        }

        $rootEntityClass = $datagrid->getConfig()->getOrmQuery()->getRootEntity();

        $configArray = $event->getConfig()->toArray();
        if (!empty($configArray['filters']['columns'])) {
            foreach ($configArray['filters']['columns'] as &$filter) {
                if ($filter['type'] === PaymentStatusFilter::NAME) {
                    // For the option info - {@see PaymentStatusFilterType}.
                    $filter['options']['target_entity'] = $rootEntityClass;
                }
            }

            // Unset the reference to avoid modifying the original array.
            unset($filter);

            $event->getConfig()->offsetSet('filters', $configArray['filters']);
        }
    }
}
