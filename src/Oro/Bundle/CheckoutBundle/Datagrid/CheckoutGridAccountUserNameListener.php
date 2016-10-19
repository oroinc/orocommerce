<?php

namespace Oro\Bundle\CheckoutBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Component\Config\Common\ConfigObject;
use Oro\Bundle\CustomerBundle\Security\AccountUserProvider;

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
        }
    }

    /**
     * @return bool
     */
    private function hasPermissionToViewAllPastCheckouts()
    {
        return $this->accountUserProvider->isGrantedViewLocal('Oro\Bundle\CheckoutBundle\Entity\Checkout');
    }
}
