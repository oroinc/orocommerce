<?php

namespace Oro\Bundle\ActionBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class WidgetController extends Controller
{
    /**
     * @Route("/buttons", name="oro_action_widget_buttons")
     * @Template()
     *
     * @param Request $request
     * @return array
     */
    public function buttonsAction(Request $request)
    {
        return [
            'actions' => $this->get('oro_action.manager')->getActions(
                [
                    'route' => $request->get('route'),
                    'entityId' => $request->get('entityId'),
                    'entityClass' => $request->get('entityClass'),
                ]
            ),
        ];
    }
}
