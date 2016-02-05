<?php

namespace OroB2B\Bundle\ProductBundle\DataGrid\Extension\RowTemplate;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;

class Extension extends AbstractExtension
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
            new Configuration(),
            ['templates' => $config->offsetGetByPath(Configuration::TEMPLATES_PATH)]
        );
    }
}
