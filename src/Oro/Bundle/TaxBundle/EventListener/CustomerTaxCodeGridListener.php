<?php

namespace Oro\Bundle\TaxBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

class CustomerTaxCodeGridListener extends TaxCodeGridListener
{
    /** {@inheritdoc} */
    protected function addColumn(DatagridConfiguration $config)
    {
        $config->offsetSetByPath(
            sprintf('[columns][%s]', $this->getDataName()),
            ['label' => $this->getColumnLabel(), 'renderable' => false]
        );
    }

    /** {@inheritdoc} */
    protected function getDataName()
    {
        return 'customerGroupTaxCode';
    }

    /** {@inheritdoc} */
    protected function getColumnLabel()
    {
        return 'oro.tax.taxcode.customergroup.label';
    }

    /** {@inheritdoc} */
    protected function getJoinAlias()
    {
        return 'customerGroupTaxCodes';
    }

    /** {@inheritdoc} */
    protected function getAlias(DatagridConfiguration $configuration)
    {
        return 'customer_group';
    }
}
