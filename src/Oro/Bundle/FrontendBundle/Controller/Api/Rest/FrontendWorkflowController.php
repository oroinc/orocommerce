<?php

namespace Oro\Bundle\FrontendBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations as Rest;

use Oro\Bundle\WorkflowBundle\Controller\Api\Rest\WorkflowController;

/**
 * @Rest\NamePrefix("oro_api_frontend_workflow_")
 */
class FrontendWorkflowController extends WorkflowController
{
}
