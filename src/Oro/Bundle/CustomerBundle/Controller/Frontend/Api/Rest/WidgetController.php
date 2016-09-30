<?php

namespace Oro\Bundle\CustomerBundle\Controller\Frontend\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;

use Oro\Bundle\SidebarBundle\Controller\Api\Rest\WidgetController as BaseController;

/**
 * @RouteResource("sidebarwidgets")
 * @NamePrefix("oro_api_frontend_")
 */
class WidgetController extends BaseController
{
    /**
     * @return string
     */
    protected function getWidgetClass()
    {
        return $this->getParameter('oro_customer.entity.widget.class');
    }
}
