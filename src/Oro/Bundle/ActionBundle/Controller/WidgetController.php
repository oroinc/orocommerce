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
        $context = $this->createContextFromRequest($request);

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
        $context = $this->createContextFromRequest($request);
        $action = $this->getActionManager()->getAction($context, $actionName);
        $actionContext = $this->getActionManager()->createActionContext($context);

        $form = $this->getActionFromManager()->getActionForm($action, $actionContext);

        $saved = false;
        if ($request->isMethod('POST')) {
            $form->submit($request);
            if ($form->isValid()) {
                $saved = true;
            }
        }
        $frontendOptions = $action->getDefinition()->getFrontendOptions();
        $template = !empty($frontendOptions['dialog_template'])
            ? $frontendOptions['dialog_template']
            : self::DEFAULT_DIALOG_TEMPLATE;

        return $this->render(
            $template,
            [
                'action' => $action,
                'saved' => $saved,
                'form' => $form->createView(),
                'context' => $context
            ]
        );
    }

    /**
     * @param Request $request
     * @return array
     */
    protected function createContextFromRequest(Request $request)
    {
        return [
            'route' => $request->get('route'),
            'entityId' => $request->get('entityId'),
            'entityClass' => $request->get('entityClass'),
        ];
    }

    /**
     * @return ActionManager
     */
    protected function getActionManager()
    {
        return $this->get('oro_action.manager');
    }

    /**
     * @return ActionFormManager
     */
    protected function getActionFromManager()
    {
        return $this->get('oro_action.form_manager');
    }
}
