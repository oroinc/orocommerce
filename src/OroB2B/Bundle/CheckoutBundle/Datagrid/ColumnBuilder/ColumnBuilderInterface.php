<?php

namespace OroB2B\Bundle\CheckoutBundle\Datagrid\ColumnBuilder;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;

interface ColumnBuilderInterface
{
    /**
     * @param ResultRecord[] $records
     */
    public function buildColumn($records);
}
