<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\EventListener;

use Oro\Bundle\CustomerBundle\Security\CustomerUserProvider;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Denies access to shopping list grid for anonymous users.
 */
class FrontendShoppingListAccessListener
{
    public function __construct(
        private CustomerUserProvider $customerUserProvider
    ) {
    }

    public function onBuildBefore(BuildBefore $event)
    {
        if (!$this->customerUserProvider->getLoggedUser()) {
            throw new AccessDeniedException();
        }
    }
}
