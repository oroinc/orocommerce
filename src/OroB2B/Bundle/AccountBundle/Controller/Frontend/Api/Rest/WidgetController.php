<?php

namespace OroB2B\Bundle\AccountBundle\Controller\Frontend\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;

use Oro\Bundle\SidebarBundle\Controller\Api\Rest\WidgetController as BaseController;

/**
 * @RouteResource("sidebarwidgets")
 * @NamePrefix("orob2b_api_frontend_")
 */
class WidgetController extends BaseController
{
    /**
     * @return string
     */
    protected function getWidgetClass()
    {
        return $this->getParameter('orob2b_account.entity.widget.class');
    }
}
