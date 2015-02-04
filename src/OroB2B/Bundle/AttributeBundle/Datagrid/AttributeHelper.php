<?php

namespace OroB2B\Bundle\AttributeBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

class AttributeHelper
{

    /**
     * Disables delete action for system attributes
     *
     * @return callable
     */
    public function getActionConfigurationClosure()
    {
        return function (ResultRecordInterface $record) {
            if ($record->getValue('system')) {
                return array('delete' => false);
            }
        };
    }
}
