<?php

namespace Oro\Bundle\SaleBundle\EventListener\Datagrid;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

/**
 * Adds "projectName" column to storefront quote datagrid
 * when the "Enable Quote Project Name" config option is enabled.
 */
class ProjectNameFrontendDatagridListener
{
    public function __construct(
        private readonly ConfigManager $configManager
    ) {
    }

    public function onBuildBefore(BuildBefore $event): void
    {
        if (!$this->configManager->get('oro_sale.enable_quote_project_name')) {
            return;
        }

        $config = $event->getConfig();
        $config->addColumn(
            'projectName',
            ['label' => 'oro.frontend.sale.quote.project_name.label'],
            'quote.projectName',
            ['data_name' => 'quote.projectName'],
            ['type' => 'string', 'data_name' => 'quote.projectName']
        );
        $config->moveColumnAfter('projectName', 'qid');
        $config->moveFilterAfter('projectName', 'qid');
    }
}
