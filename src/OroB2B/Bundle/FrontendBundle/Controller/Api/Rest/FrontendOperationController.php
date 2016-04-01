<?php

namespace OroB2B\Bundle\FrontendBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations as Rest;

use Oro\Bundle\ActionBundle\Controller\Api\Rest\OperationController;

/**
 * @Rest\RouteResource("operations")
 * @Rest\NamePrefix("orob2b_api_frontend_action_")
 */
class FrontendOperationController extends OperationController
{
}
