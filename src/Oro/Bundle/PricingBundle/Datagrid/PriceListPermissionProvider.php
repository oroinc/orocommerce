<?php

namespace Oro\Bundle\PricingBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

/**
 * Provides permission checks for price list actions in datagrids.
 *
 * Determines which actions (such as delete or set as default) are allowed for a price list
 * based on its current state. Prevents modification of default price lists.
 */
class PriceListPermissionProvider
{
    /**
     * @param ResultRecordInterface $record
     * @param array $actions
     * @return array
     */
    public function getPermissions(ResultRecordInterface $record, array $actions)
    {
        $actions = array_keys($actions);
        $permissions = [];
        foreach ($actions as $action) {
            $permissions[$action] = true;
        }

        if (array_key_exists('default', $permissions)) {
            $permissions['default'] = !$record->getValue('default');
        }

        if (array_key_exists('delete', $permissions)) {
            $permissions['delete'] = !$record->getValue('default');
        }

        return $permissions;
    }
}
