<?php

namespace OroB2B\Bundle\FrontendBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;

use Oro\Bundle\WindowsBundle\Controller\Api\WindowsStateController;

/**
 * @RouteResource("windows")
 * @NamePrefix("orob2b_frontend_api_")
 */
class FrontendWindowsStateController extends WindowsStateController
{
}
