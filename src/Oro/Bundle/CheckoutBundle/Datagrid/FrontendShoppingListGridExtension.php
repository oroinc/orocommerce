<?php

namespace Oro\Bundle\CheckoutBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionExtension;

/**
 * Unset excess actions from the grid configuration.
 */
class FrontendShoppingListGridExtension extends AbstractExtension
{
    /** @var array */
    private $grids;

    /** @var array */
    private $actions;

    /** @var int */
    private $priority;

    public function __construct(array $grids, array $actions, int $priority)
    {
        $this->grids = $grids;
        $this->actions = $actions;
        $this->priority = $priority;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config): bool
    {
        return \in_array($config->getName(), $this->grids, true) && parent::isApplicable($config);
    }

    /**
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config): void
    {
        $actions = $config->offsetGetOr(ActionExtension::ACTION_KEY, []);
        foreach ($this->actions as $action) {
            if ($this->priority > 0) {
                $actions[$action] = null;
            } else {
                unset($actions[$action]);
            }
        }

        $config->offsetSet(ActionExtension::ACTION_KEY, $actions);
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority(): int
    {
        return $this->priority;
    }
}
