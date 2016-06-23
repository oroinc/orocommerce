<?php

namespace OroB2B\Bundle\CheckoutBundle\Datagrid\ColumnResolver;

use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;

interface ColumnResolverInterface
{
    /**
     * @param OrmResultAfter $event
     */
    public function resolveColumn(OrmResultAfter $event);
}
