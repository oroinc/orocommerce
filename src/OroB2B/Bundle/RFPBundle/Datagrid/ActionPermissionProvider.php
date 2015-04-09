<?php

namespace OroB2B\Bundle\RFPBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

class ActionPermissionProvider
{
    /**
     * @param ResultRecordInterface $record
     *
     * @return array
     */
    public function getRequestStatusDefinitionPermissions(ResultRecordInterface $record)
    {
        $isDeleted = $record->getValue('deleted');

        return [
            'restore' => $isDeleted,
            'delete'  => !$isDeleted,
            'view'    => true,
            'update'  => true
        ];
    }
}
