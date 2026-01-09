<?php

namespace Oro\Bundle\CheckoutBundle\Datagrid;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CustomerBundle\Security\CustomerUserProvider;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

/**
 * Handles checkout datagrid configuration to control customer user name visibility.
 *
 * Listens to datagrid build events and removes the customer user name column, sorter, and filter
 * if the current user lacks permission to view all past checkouts.
 */
class CheckoutGridCustomerUserNameListener
{
    /**
     * @var CustomerUserProvider
     */
    private $customerUserProvider;

    public function __construct(CustomerUserProvider $customerUserProvider)
    {
        $this->customerUserProvider = $customerUserProvider;
    }

    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();

        $columns = $config->offsetGetByPath('[columns]');

        if (isset($columns['customerUserName']) && !$this->hasPermissionToViewAllPastCheckouts()) {
            $config->offsetUnsetByPath('[columns][customerUserName]');
            $config->offsetUnsetByPath('[sorters][columns][customerUserName]');
            $config->offsetUnsetByPath('[filters][columns][customerUserName]');
        }
    }

    /**
     * @return bool
     */
    private function hasPermissionToViewAllPastCheckouts()
    {
        return $this->customerUserProvider->isGrantedViewCustomerUser(Checkout::class);
    }
}
