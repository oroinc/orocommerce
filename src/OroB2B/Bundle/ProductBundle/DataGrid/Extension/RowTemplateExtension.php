<?php

namespace OroB2B\Bundle\ProductBundle\DataGrid\Extension;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;

class RowTemplateExtension extends AbstractExtension
{
    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        $this->validateConfiguration(
            new RowTemplateConfiguration(),
            ['templates' => $config->offsetGetByPath(RowTemplateConfiguration::TEMPLATES_PATH)]
        );
    }
}
