<?php

namespace Oro\Bundle\CustomerBundle\Controller\Frontend\Api\Rest;

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
        return $this->getParameter('oro_customer.entity.pinbar_tab.class');
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserClass()
    {
        return $this->getParameter('oro_customer.entity.account_user.class');
    }
}
