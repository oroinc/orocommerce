<?php

namespace Oro\Bundle\RuleBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\RuleBundle\Entity\RuleInterface;

/**
 * Determines the visibility of rule actions in datagrids based on rule state.
 *
 * This provider controls which actions (enable/disable) should be visible for each rule record in a datagrid.
 * It evaluates the current state of a rule and adjusts action visibility accordingly, ensuring that only applicable
 * actions are shown (e.g., hide the "enable" action if the rule is already enabled, and hide the "disable" action
 * if the rule is already disabled).
 */
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
