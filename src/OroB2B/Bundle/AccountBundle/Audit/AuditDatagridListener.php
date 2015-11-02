<?php

namespace OroB2B\Bundle\AccountBundle\Audit;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProviderInterface;

class AuditDatagridListener
{
    const CUSTOM_AUDIT_GRID = 'b2b-audit-grid';
    const CUSTOM_HISTORY_AUDIT_GRID = 'b2b-audit-history-grid';

    /**
     * @param ConfigurationProviderInterface $configurationProvider
     */
    public function __construct(ConfigurationProviderInterface $configurationProvider)
    {
        $this->configurationProvider = $configurationProvider;
    }

    /**
     * @param BuildBefore $event
     */
    public function onHistoryBuildBefore(BuildBefore $event)
    {
        $this->replaceConfiguration(static::CUSTOM_HISTORY_AUDIT_GRID, $event->getConfig());
    }

    /**
     * @param BuildBefore $event
     */
    public function onAuditBuildBefore(BuildBefore $event)
    {
        $this->replaceConfiguration(static::CUSTOM_AUDIT_GRID, $event->getConfig());
    }

    /**
     * @param string                $gridName
     * @param DatagridConfiguration $configuration
     */
    protected function replaceConfiguration($gridName, DatagridConfiguration $configuration)
    {
        $datagridConfiguration = $this->configurationProvider->getConfiguration($gridName);
        foreach ($datagridConfiguration as $key => $value) {
            // Don't replace name of original datagrid
            if ($key !== 'name') {
                $configuration->offsetSet($key, $value);
            }
        }
    }
}
