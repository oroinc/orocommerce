<?php

namespace OroB2B\Bundle\CheckoutBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Component\Config\Common\ConfigObject;

use OroB2B\Bundle\AccountBundle\Security\AccountUserProvider;

class CheckoutGridAccountUserNameListener
{
    /**
     * @var AccountUserProvider
     */
    private $accountUserProvider;

    /**
     * @param AccountUserProvider $accountUserProvider
     */
    public function __construct(
        AccountUserProvider $accountUserProvider
    ) {
        $this->accountUserProvider = $accountUserProvider;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();

        $columns = $config->offsetGetByPath('[columns]');

        if (isset($columns['accountUserName']) && !$this->hasPermissionToViewAllPastCheckouts()) {
            $config->offsetUnsetByPath('[columns][accountUserName]');
            $config->offsetUnsetByPath('[sorters][columns][accountUserName]');
            $this->normalizeColumnOrder($config);
        }
    }

    /**
     * @return bool
     */
    private function hasPermissionToViewAllPastCheckouts()
    {
        return $this->accountUserProvider->isGrantedViewLocal('OroB2B\Bundle\OrderBundle\Entity\Order');
    }

    /**
     * This function modifies all column orders
     * by making the min order value as the base 0 value.
     *
     * @param ConfigObject $config
     * @return array|null
     */
    private function normalizeColumnOrder($config)
    {
        if (empty($config)) {
            return;
        }

        $columns = $config->offsetGetByPath('[columns]');

        if (0 === count($columns)) {
            return;
        }

        $orderValues = array_column($columns, 'order');

        $minOffset = min($orderValues);

        if (0 === $minOffset) {
            return;
        }
        
        foreach ($columns as $key => $column) {
            if (!isset($column['order'])) {
                continue;
            }

            $columns[$key]['order'] = $column['order'] - $minOffset;
        }

        $config->offsetSetByPath('[columns]', $columns);
    }
}
