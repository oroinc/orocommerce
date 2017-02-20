<?php

namespace Oro\Bundle\RuleBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\RuleBundle\Entity\RuleInterface;

class RuleActionsVisibilityProvider
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

        $rule = $record->getValue('rule');

        if ($rule instanceof RuleInterface) {
            if (array_key_exists('enable', $visibility)) {
                $visibility['enable'] = !$rule->isEnabled();
            }

            if (array_key_exists('disable', $visibility)) {
                $visibility['disable'] = $rule->isEnabled();
            }
        }

        return $visibility;
    }
}
