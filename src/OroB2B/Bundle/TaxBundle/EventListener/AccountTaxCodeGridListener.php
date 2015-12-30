<?php

namespace OroB2B\Bundle\TaxBundle\EventListener;

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

    /**
     * @return string
     */
    protected function getDataName()
    {
        return 'accountGroupTaxCode';
    }

    /**
     * @return string
     */
    protected function getColumnLabel()
    {
        return 'orob2b.tax.taxcode.accountgroup.label';
    }

    /**
     * @return string
     */
    protected function getJoinAlias()
    {
        return 'accountGroupTaxCodes';
    }
}
