<?php

namespace OroB2B\Bundle\ProductBundle\DataGrid\Extension\Theme;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;

class ThemeExtension extends AbstractExtension
{
    const GRID_NAME = 'frontend-products-grid';

    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return $config->getName() === self::GRID_NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data)
    {
        parent::visitMetadata($config, $data);
        \Symfony\Component\VarDumper\VarDumper::dump($data);
    }
}
