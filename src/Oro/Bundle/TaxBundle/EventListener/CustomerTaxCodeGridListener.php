<?php

namespace Oro\Bundle\TaxBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

/**
 * Adds taxCode field to the customers grid.
 */
class CustomerTaxCodeGridListener extends TaxCodeGridListener
{
    /**
     * {@inheritDoc}
     */
    protected function addColumn(DatagridConfiguration $config): void
    {
        $config->offsetSetByPath(
            sprintf('[columns][%s]', $this->getDataName()),
            ['label' => $this->getColumnLabel(), 'renderable' => false]
        );
    }

    protected function getDataName(): string
    {
        return 'customerGroupTaxCode';
    }

    /**
     * {@inheritDoc}
     */
    protected function getColumnLabel(): string
    {
        return 'oro.tax.taxcode.customergroup.label';
    }

    /**
     * {@inheritDoc}
     */
    protected function getJoinAlias(): string
    {
        return 'customerGroupTaxCodes';
    }

    /**
     * {@inheritDoc}
     */
    protected function getAlias(DatagridConfiguration $configuration): string
    {
        return 'customer_group';
    }
}
