<?php

namespace Oro\Bundle\ActionBundle\Helper;

use Oro\Bundle\ActionBundle\Model\Action;

class RestrictHelper
{
    /**
     * $groups param can be array of groups, null or string with group
     * if $groups is null - restrict only actions which not have group
     * if $groups define as array - restrict only actions which in this array with groups
     * if $groups define as string - restrict only actions which equals this string with group
     *
     * @param Action[] $actions
     * @param array|null|string $groups
     * @return Action[]
     */
    public function restrictActionsByGroup($actions, $groups)
    {
        $groups = $groups === null ? null : (array)$groups;
        $restrictedActions = [];
        foreach ($actions as $key => $action) {
            $buttonOptions = $action->getDefinition()->getButtonOptions();
            if (array_key_exists('group', $buttonOptions)) {
                if ($groups !== null && in_array($buttonOptions['group'], $groups)) {
                    $restrictedActions[$key] = $action;
                }
            } elseif ($groups === null) {
                $restrictedActions[$key] = $action;
            }
        }

        return $restrictedActions;
    }
}
