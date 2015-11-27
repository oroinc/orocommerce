<?php

namespace Oro\Bundle\ActionBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ActionBundle\Model\ActionFormManager;
use Oro\Bundle\ActionBundle\Model\ActionManager;

class WidgetController extends Controller
{
    const DEFAULT_DIALOG_TEMPLATE = 'OroActionBundle:Widget:widget/form.html.twig';

    /**
     * @Route("/buttons", name="oro_action_widget_buttons")
     * @Template()
     *
     * @param Request $request
     * @return array
     */
    public function buttonsAction(Request $request)
    {
        $context = [
            'route' => $request->get('route'),
            'entityId' => $request->get('entityId'),
            'entityClass' => $request->get('entityClass'),
        ];

        return [
            'actions' => $this->getActionManager()->getActions($context),
            'context' => $context
        ];
    }

    /**
     * @Route("/form/{actionName}", name="oro_action_widget_form")
     * @param Request $request
     * @param string $actionName
     * @return Response
     */
    public function formAction(Request $request, $actionName)
    {
        $context = [
            'route' => $request->get('route'),
            'entityId' => $request->get('entityId'),
            'entityClass' => $request->get('entityClass'),
        ];
        /** @var ActionFormManager $formManager */
        $formManager = $this->get('oro_action.form_manager');
        $action = $this->getActionManager()->getAction($context, $actionName);
        $actionContext = $this->getActionManager()->createActionContext($context);

        $form = $formManager->getActionForm($action, $actionContext);

        $saved = false;
        if ($request->isMethod('POST')) {
            $form->submit($request);
            if ($form->isValid()) {
                $saved = true;
            }
        }

        return $this->render(
            self::DEFAULT_DIALOG_TEMPLATE,
            [
                'action' => $action,
                'saved' => $saved,
                'form' => $form->createView(),
                'context' => $context
            ]
        );
    }

    /**
     * @return ActionManager
     */
    protected function getActionManager()
    {
        return $this->get('oro_action.manager');
    }
}
