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
        $configuration = $this->container->get('oro_action.configuration.config.action_list');
        $bundles = $this->container->getParameter('kernel.bundles');

        $provider = new \Oro\Bundle\ActionBundle\Configuration\ActionConfigurationProvider($bundles, $configuration);

        $conf = $provider->getWorkflowDefinitionConfiguration();

        p(array('conf' => $conf));
        exit();

        return [
            'actions' => [
                [
                    'label' => 'action1',
                ]
            ],
        ];
    }
}
