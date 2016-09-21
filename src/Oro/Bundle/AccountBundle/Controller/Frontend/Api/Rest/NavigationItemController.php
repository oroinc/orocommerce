<?php

namespace Oro\Bundle\AccountBundle\Controller\Frontend\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;

use Oro\Bundle\NavigationBundle\Controller\Api\NavigationItemController as BaseNavigationItemController;

/**
 * @RouteResource("navigationitems")
 * @NamePrefix("oro_api_frontend_")
 */
class NavigationItemController extends BaseNavigationItemController
{
    /**
     * {@inheritdoc}
     */
    protected function getPinbarTabClass()
    {
        return $this->getParameter('oro_account.entity.pinbar_tab.class');
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserClass()
    {
        return $this->getParameter('oro_account.entity.account_user.class');
    }
}
