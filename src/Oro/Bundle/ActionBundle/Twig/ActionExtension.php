<?php

namespace Oro\Bundle\ActionBundle\Twig;

use Oro\Bundle\ActionBundle\Model\ActionManager;

class ActionExtension extends \Twig_Extension
{
    const NAME = 'oro_action';

    /**
     * @var ActionManager
     */
    protected $manager;

    /**
     * @param ActionManager $manager
     */
    public function __construct(ActionManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction(
                'oro_action_widget_parameters',
                [$this, 'getWidgetParameters'],
                ['needs_context' => true]
            ),
        );
    }

    /**
     *
     * @param array $context
     * @return array
     */
    public function getWidgetParameters($context)
    {
        $params = [];

        if (isset($context['entity'])) {
            // TODO: find entity identifier() and encode url parameters
            $params['entity_id'] = $context['entity']->getId();
            $params['entity_class'] = get_class($context['entity']);
        } elseif (isset($context['entity_class'])) {
            $params['entity_class'] = $context['entity_class'];
        }
        return $params;
    }
}
