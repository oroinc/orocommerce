<?php

namespace Oro\Bundle\RFPBundle\EventListener\Datagrid;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

/**
 * Adds "projectName" column to RFQ datagrid when the "Enable RFQ Project Name" config option is enabled.
 */
class ProjectNameDatagridListener
{
    public function __construct(
        private readonly ConfigManager $configManager
    ) {
    }

    public function onBuildBefore(BuildBefore $event): void
    {
        if (!$this->configManager->get('oro_rfp.enable_rfq_project_name')) {
            return;
        }

        $config = $event->getConfig();
        $config->addColumn(
            'projectName',
            ['label' => 'oro.rfp.request.project_name.label'],
            'request.projectName',
            ['data_name' => 'request.projectName'],
            ['type' => 'string', 'data_name' => 'request.projectName']
        );
        $config->moveColumnAfter('projectName', 'id');
        $config->moveFilterAfter('projectName', 'id');
    }
}
