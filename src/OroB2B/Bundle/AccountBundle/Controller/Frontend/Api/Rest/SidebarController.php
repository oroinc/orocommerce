<?php

namespace OroB2B\Bundle\AccountBundle\Controller\Frontend\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;

use Oro\Bundle\SidebarBundle\Controller\Api\Rest\SidebarController as BaseController;

/**
 * @RouteResource("sidebars")
 * @NamePrefix("orob2b_api_frontend_")
 */
class SidebarController extends BaseController
{
    /**
     * @return string
     */
    protected function getSidebarStateClass()
    {
        return $this->getParameter('orob2b_account.entity.sidebar_state.class');
    }
}
