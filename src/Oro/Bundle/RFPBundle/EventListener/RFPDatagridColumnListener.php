<?php

namespace Oro\Bundle\RFPBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\WorkflowBundle\Datagrid\WorkflowStepColumnListener;

class RFPDatagridColumnListener
{
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();
        $columns = $config->offsetGetByPath('[columns]', []);
        if (!array_key_exists(WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN, $columns)) {
            return;
        }

        $columns[WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN]['renderable'] = false;
        $config->offsetSetByPath('[columns]', $columns);
    }
}
