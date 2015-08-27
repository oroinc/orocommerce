<?php

namespace OroB2B\Bundle\AccountBundle\Controller\Frontend\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;

use Oro\Bundle\NavigationBundle\Controller\Api\NavigationItemController as BaseNavigationItemController;

/**
 * @RouteResource("navigationitems")
 * @NamePrefix("orob2b_api_frontend_")
 */
class NavigationItemController extends BaseNavigationItemController
{
    /**
     * {@inheritdoc}
     */
    protected function getPinbarTabClass()
    {
        return $this->getParameter('orob2b_account.entity.pinbar_tab.class');
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserClass()
    {
        return $this->getParameter('orob2b_account.entity.account_user.class');
    }
}
