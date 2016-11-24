<?php

namespace Oro\Bundle\FrontendBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * @Route("/workflowwidget")
 */
class WorkflowWidgetController extends Controller
{

    /**
     * @Route("/buttons/{entityClass}/{entityId}", name="oro_frontend_workflow_widget_buttons")
     *
     * @param Request $request
     * @return Response
     */
    public function buttonsAction(Request $request)
    {
        return $this->forward(
            'OroWorkflowBundle:Widget:buttons',
            $request->attributes->all(),
            $request->query->all()
        );
    }

    /**
     * @Route(
     *      "/transition/create/attributes/{workflowName}/{transitionName}",
     *      name="oro_frontend_workflow_widget_start_transition_form"
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function startTransitionFormAction(Request $request)
    {
        return $this->forward(
            'OroWorkflowBundle:Widget:startTransitionForm',
            $request->attributes->all(),
            $request->query->all()
        );
    }
}
