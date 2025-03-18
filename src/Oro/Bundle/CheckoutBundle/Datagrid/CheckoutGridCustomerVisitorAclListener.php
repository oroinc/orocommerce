<?php

namespace Oro\Bundle\CheckoutBundle\Datagrid;

use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Denies access for anonymous users to 'frontend-checkouts-grid'
 * and 'frontend-customer-dashboard-my-checkouts-grid' datagrids.
 */
class CheckoutGridCustomerVisitorAclListener
{
    public function __construct(
        private TokenStorageInterface $tokenStorage
    ) {
    }

    public function onBuildBefore(BuildBefore $event): void
    {
        if ($this->tokenStorage->getToken() instanceof AnonymousCustomerUserToken) {
            throw new AccessDeniedException('Anonymous users are not allowed.');
        }
    }
}
