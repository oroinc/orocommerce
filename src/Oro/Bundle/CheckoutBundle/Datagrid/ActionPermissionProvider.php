<?php

namespace Oro\Bundle\CheckoutBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

class ActionPermissionProvider
{
    /**
     * @param ResultRecordInterface $record
     *
     * @return array
     */
    public function getPermissions(ResultRecordInterface $record)
    {
        $completed = $record->getValue('completed');

        return [
            'view' => !$completed
        ];
    }
}
