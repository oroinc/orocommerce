<?php

namespace OroB2B\Bundle\PricingBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

class DefaultActionPermissionProvider
{
    const DEFAULT_ACTION_KEY = 'is_default';

    public function getPermissions(ResultRecordInterface $record, array $actions)
    {
        $actions = array_keys($actions);
        $permissions = [];
        foreach ($actions as $action) {
            $permissions[$action] = true;
        }

        if (array_key_exists('default', $permissions)) {
            $permissions['default'] = !$record->getValue(self::DEFAULT_ACTION_KEY);
        }

        return $permissions;
    }
}
