<?php

namespace OroB2B\Bundle\AccountBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

class ActionPermissionProvider
{
    /**
     * @param ResultRecordInterface $record
     *
     * @return array
     */
    public function getUserPermissions(ResultRecordInterface $record)
    {
        $enabled = $record->getValue('enabled');

        return [
            'enable'  => !$enabled,
            'disable' => $enabled,
            'view'    => true,
            'update'  => true,
            'delete'  => true
        ];
    }
}
