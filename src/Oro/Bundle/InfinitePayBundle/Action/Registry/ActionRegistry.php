<?php

namespace Oro\Bundle\InfinitePayBundle\Action\Registry;

use Oro\Bundle\InfinitePayBundle\Action\ActionInterface;

class ActionRegistry implements ActionRegistryInterface
{
    /**
     * @var ActionInterface[]
     */
    protected $actions = [];

    /**
     * @param $actionType
     * @param ActionInterface $actionClass
     */
    public function addAction($actionType, ActionInterface $actionClass)
    {
        $this->actions[$actionType] = $actionClass;
    }

    /**
     * @param string $actionType
     *
     * @return ActionInterface
     *
     * @throws \InvalidArgumentException
     */
    public function getActionByType($actionType)
    {
        if (!array_key_exists($actionType, $this->actions)) {
            throw new \InvalidArgumentException(sprintf('InfinitePay action "%s" not registered', $actionType));
        }

        return $this->actions[$actionType];
    }
}
