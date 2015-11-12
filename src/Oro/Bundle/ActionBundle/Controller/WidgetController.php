<?php

namespace Oro\Bundle\ActionBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

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
        /* @var $requestStack \Symfony\Component\HttpFoundation\RequestStack */
        $requestStack = $this->get('request_stack');

        $context = [
            'route' => $requestStack->getMasterRequest()->get('_route'),
            'entityId' => $this->getRequest()->get('entity_id'),
            'entityClass' => $this->getRequest()->get('entity_class'),
        ];

        /* @var $manager \Oro\Bundle\ActionBundle\Model\ActionManager */
        $manager = $this->get('oro_action.manager');

        return [
            'actions' => $manager->getActions($context),
        ];
    }
}
