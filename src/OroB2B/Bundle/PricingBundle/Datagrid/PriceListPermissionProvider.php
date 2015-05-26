<?php

namespace OroB2B\Bundle\PricingBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

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
