<?php

namespace Oro\Bundle\CheckoutBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

/**
 * Provides action permissions for checkout records in datagrids.
 *
 * Determines which actions are available for checkout records based on their completion status,
 * restricting certain actions for completed checkouts.
 */
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
