<?php

namespace Oro\Bundle\ActionBundle\Controller;

use Doctrine\Common\Collections\ArrayCollection;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ActionBundle\Model\ActionManager;

class WidgetController extends Controller
{
    /**
     * @Route("/buttons", name="oro_action_widget_buttons")
     * @Template()
     *
     * @return array
     */
    public function buttonsAction()
    {
        $context = $this->get('oro_action.helper.context')->getContext();

        return [
            'actions' => $this->getActionManager()->getActions($context),
            'context' => $context
        ];
    }

    /**
     * @Route("/form/{actionName}", name="oro_action_widget_form")
     *
     * @param Request $request
     * @param string $actionName
     * @return Response
     */
    public function formAction(Request $request, $actionName)
    {
        $params = [
            'errors' => new ArrayCollection(),
        ];

        try {
            /** @var Form $form */
            $form = $this->get('oro_action.form_manager')->getActionForm($actionName);

            if ($this->submitForm($request, $form)) {
                $context = $this->getActionManager()->execute($actionName, $params['errors']);

                if ($context) {
                    $params['response'] = [];
                    if ($context->getRedirectUrl()) {
                        $params['response']['redirectUrl'] = $context->getRedirectUrl();
                    }
                }
            }

            $params['form'] = $form->createView();
        } catch (\Exception $e) {
            $params['errors']->add([
                'message' => $e->getMessage(),
            ]);
        }

        return $this->render($this->getActionManager()->getDialogTemplate($actionName), $params);
    }

    /**
     * @param Request $request
     * @param Form $form
     * @return boolean
     */
    protected function submitForm(Request $request, Form $form)
    {
        if (!$request->isMethod('POST')) {
            return false;
        }

        $form->submit($request);

        return $form->isValid();
    }

    /**
     * @return ActionManager
     */
    protected function getActionManager()
    {
        return $this->get('oro_action.manager');
    }
}
