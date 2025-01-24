<?php

namespace Oro\Bundle\SaleBundle\EventListener\Datagrid;

use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Extension\GridViews\GridViewsExtension;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
* Disable grid views functionality to frontend datagrids for guest
*/
class FrontendGuestGridViewsListener
{
    private TokenStorageInterface $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function onPreBuild(PreBuild $event): void
    {
        if ($this->tokenStorage->getToken() instanceof AnonymousCustomerUserToken) {
            $event->getParameters()->set(
                GridViewsExtension::GRID_VIEW_ROOT_PARAM,
                [GridViewsExtension::DISABLED_PARAM => true]
            );
        }
    }
}
