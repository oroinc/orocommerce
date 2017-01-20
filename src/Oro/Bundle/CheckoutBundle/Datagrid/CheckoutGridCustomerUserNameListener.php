<?php

namespace Oro\Bundle\CheckoutBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Component\Config\Common\ConfigObject;
use Oro\Bundle\CustomerBundle\Security\CustomerUserProvider;

class CheckoutGridCustomerUserNameListener
{
    /**
     * @var CustomerUserProvider
     */
    private $customerUserProvider;

    /**
     * @param CustomerUserProvider $customerUserProvider
     */
    public function __construct(
        CustomerUserProvider $customerUserProvider
    ) {
        $this->customerUserProvider = $customerUserProvider;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();

        $columns = $config->offsetGetByPath('[columns]');

        if (isset($columns['customerUserName']) && !$this->hasPermissionToViewAllPastCheckouts()) {
            $config->offsetUnsetByPath('[columns][customerUserName]');
            $config->offsetUnsetByPath('[sorters][columns][customerUserName]');
        }
    }

    /**
     * @return bool
     */
    private function hasPermissionToViewAllPastCheckouts()
    {
        return $this->customerUserProvider->isGrantedViewLocal('Oro\Bundle\CheckoutBundle\Entity\Checkout');
    }
}
