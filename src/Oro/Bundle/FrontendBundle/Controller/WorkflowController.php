<?php

namespace Oro\Bundle\FrontendBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/workflow")
 */
class WorkflowController extends Controller
{
    /**
     * @Route(
     *      "/start/{workflowName}/{transitionName}",
     *      name="oro_frontend_workflow_start_transition_form"
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function startTransitionAction(Request $request)
    {
        return $this->forward(
            'OroWorkflowBundle:Workflow:startTransition',
            $request->attributes->all(),
            $request->query->all()
        );
    }
}
