<?php

namespace OroB2B\Bundle\AttributeBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

class AttributeHelper
{
    /**
     * Disables delete action for system attributes
     *
     * @param ResultRecordInterface $record
     * @return array
     */
    public static function getActionConfiguration(ResultRecordInterface $record)
    {
        $isSystem = $record->getValue('system');

        return [
            'delete' => empty($isSystem)
        ];
    }
}
