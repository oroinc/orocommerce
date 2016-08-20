<?php

namespace Oro\Bundle\TaxBundle\EventListener;

use Doctrine\ORM\Query\Expr;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

class AccountTaxCodeGridListener extends TaxCodeGridListener
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
        return 'accountGroupTaxCode';
    }

    /** {@inheritdoc} */
    protected function getColumnLabel()
    {
        return 'oro.tax.taxcode.accountgroup.label';
    }

    /** {@inheritdoc} */
    protected function getJoinAlias()
    {
        return 'accountGroupTaxCodes';
    }

    /** {@inheritdoc} */
    protected function getAlias(DatagridConfiguration $configuration)
    {
        return 'account_group';
    }
}
