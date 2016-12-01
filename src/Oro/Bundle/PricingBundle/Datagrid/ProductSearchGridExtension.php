<?php

namespace Oro\Bundle\PricingBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;

class ProductSearchGridExtension extends AbstractExtension
{
    const SUPPORTED_GRID = 'frontend-product-search-grid';

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return static::SUPPORTED_GRID == $config->getName() && $config->getDatasourceType() == 'search';
    }

    /**
     * {@inheritDoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        $config->addSelect('decimal.minimum_price_CPL_ID_CURRENCY as price');
        $config->addColumn('price', [
            'label' => 'oro.pricing.price.label',
            'type' => 'money_value',
            'frontend_type' => 'string',
            'translatable' => true,
            'editable' => false,
            'renderable' => false
        ]);
        $config->addSorter('price', ['data_name' => 'minimum_price_CPL_ID_CURRENCY', 'type' => 'decimal']);
    }
}
