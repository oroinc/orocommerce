<?php

namespace Oro\Bundle\ActionBundle\Twig;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\ActionBundle\Model\ActionManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class ActionExtension extends \Twig_Extension
{
    const NAME = 'oro_action';

    /** @var ActionManager */
    protected $manager;

    /** @var ActionManager */
    protected $doctrineHelper;

    /** @var RequestStack */
    protected $requestStack;

    /**
     * @param ActionManager $manager
     * @param DoctrineHelper $doctrineHelper
     * @param RequestStack $requestStack
     */
    public function __construct(ActionManager $manager, DoctrineHelper $doctrineHelper, RequestStack $requestStack)
    {
        $this->manager = $manager;
        $this->doctrineHelper = $doctrineHelper;
        $this->requestStack = $requestStack;
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
            new \Twig_SimpleFunction('has_actions', [$this, 'hasActions'], ['needs_context' => true]),
        );
    }

    /**
     * @param array $context
     * @param bool $buildQuery
     * @return array
     */
    public function getWidgetParameters(array $context, $buildQuery = true)
    {
        $params = ['route' => $this->requestStack->getMasterRequest()->get('_route')];

        if (array_key_exists('entity', $context) && is_object($context['entity'])) {
            $identifier = $this->doctrineHelper->getEntityIdentifier($context['entity']);

            $params['entityId'] = $buildQuery ? http_build_query($identifier) : $identifier;
            $params['entityClass'] = get_class($context['entity']);
        } elseif (isset($context['entity_class'])) {
            $params['entityClass'] = $context['entity_class'];
        }

        return $params;
    }

    /**
     * @param array $context
     * @return bool
     */
    public function hasActions(array $context)
    {
        return $this->manager->hasActions($this->getWidgetParameters($context, false));
    }
}
