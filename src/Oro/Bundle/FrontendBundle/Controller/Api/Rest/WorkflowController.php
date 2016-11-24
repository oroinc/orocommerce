<?php

namespace Oro\Bundle\FrontendBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Rest\NamePrefix("oro_api_frontend_workflow_")
 */
class WorkflowController extends FOSRestController
{
    /**
     * @Rest\Get(
     *      "/api/rest/{version}/workflow/start/{workflowName}/{transitionName}",
     *      requirements={"version"="latest|v1"},
     *      defaults={"version"="latest", "_format"="json"}
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function startAction(Request $request)
    {
        return $this->forward(
            'OroWorkflowBundle:Api\Rest\Workflow:start',
            $request->attributes->all(),
            $request->query->all()
        );
    }
}