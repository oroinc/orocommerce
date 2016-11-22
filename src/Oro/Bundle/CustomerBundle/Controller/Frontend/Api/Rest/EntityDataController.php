<?php

namespace Oro\Bundle\CustomerBundle\Controller\Frontend\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;

use Oro\Bundle\EntityBundle\Controller\Api\Rest\EntityDataController as BaseController;

/**
 * @RouteResource("entity_data")
 * @NamePrefix("oro_api_frontend_")
 */
class EntityDataController extends BaseController
{
}
