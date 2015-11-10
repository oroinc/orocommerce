<?php

namespace Oro\Bundle\ActionBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * @Route("/widget")
 */
class WidgetController extends Controller
{
    /**
     * @Route("/buttons", name="oro_action_widget_buttons")
     * @Template()
     */
    public function buttonsAction()
    {
        return [
            'actions' => [
                [
                    'label' => 'action1',
                ]
            ],
        ];
    }
}
