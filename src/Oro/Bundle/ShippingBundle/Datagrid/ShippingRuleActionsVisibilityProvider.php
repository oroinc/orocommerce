<?php

namespace Oro\Bundle\ShippingBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

class ShippingRuleActionsVisibilityProvider
{
    /**
     * @param ResultRecordInterface $record
     * @param array $actions
     * @return array
     */
    public function getActionsVisibility(ResultRecordInterface $record, array $actions)
    {
        $actions = array_keys($actions);
        $visibility = [];
        foreach ($actions as $action) {
            $visibility[$action] = true;
        }

        if (array_key_exists('enable', $visibility)) {
            $visibility['enable'] = !$record->getValue('enabled');
        }

        if (array_key_exists('disable', $visibility)) {
            $visibility['disable'] = $record->getValue('enabled');
        }

        return $visibility;
    }
}
