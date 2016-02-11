<?php

namespace Oro\Bundle\ActionBundle\Helper;

use Oro\Bundle\ActionBundle\Model\Action;

class RestrictHelper
{
    /**
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
