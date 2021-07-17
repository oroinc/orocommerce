<?php

namespace Oro\Bundle\CheckoutBundle\Datagrid;

use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Denies access for anonymous users to `frontend-checkouts-grid` datagrid.
 */
class CheckoutGridCustomerVisitorAclListener
{
    /** @var TokenStorageInterface */
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function onBuildBefore(BuildBefore $event)
    {
        if ($this->tokenStorage->getToken() instanceof AnonymousCustomerUserToken) {
            throw new AccessDeniedException('Anonymous users are not allowed.');
        }
    }
}
