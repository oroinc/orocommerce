<?php

namespace Oro\Bundle\ActionBundle\Model;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
//use Doctrine\Common\Persistence\ManagerRegistry;
//use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ActionBundle\Configuration\ActionConfigurationProvider;
use Oro\Bundle\ActionBundle\Model\ActionDefinition;

use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

class ActionManager
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var ActionConfigurationProvider
     */
    protected $configurationProvider;

    /**
     * @var ConditionFactory
     */
    protected $conditionFactory;


    public function __construct(
        Registry $registry,
        ActionConfigurationProvider $configurationProvider,
        ConditionFactory $conditionFactory
    ) {
        $this->registry = $registry;
        $this->configurationProvider = $configurationProvider;
        $this->conditionFactory = $conditionFactory;
    }

//    protected function findActionByContext()
//    {
//    }

    /**
     * @param array $config
     * @param string $name
     * @return ActionDefinition
     */
    protected function assembleDefinition($name, array $config)
    {
        $definition = new ActionDefinition();
        $definition
            ->setName($name)
            ->setLabel($config['label']);
        if (isset($config['applications'])) {
            $definition->setApplications($config['applications']);
        }
        if (isset($config['entities'])) {
            $definition->setEntities($config['entities']);
        }
        if (isset($config['routes'])) {
            $definition->setRoutes($config['routes']);
        }
        if (isset($config['order'])) {
            $definition->setOrder($config['order']);
        }
        if (isset($config['enabled'])) {
            $definition->setEnabled($config['enabled']);
        }
        if (isset($config['frontend_options'])) {
            $definition->setFrontendOptionsConfiguration($config['frontend_options']);
        }

        return $definition;
    }

    protected function createActionModel(ActionDefinition $definition)
    {
        $action = new Action($this->conditionFactory, $definition);
        // Context ???
        return $action;
    }

    protected function findEntity($class, $identifier)
    {
        /* @var $manager EntityManager */
        $manager = $this->registry->getManagerForClass($class);
        return $manager->getReference($class, $identifier);
    }

    /**
     * @param array $context
     * @return ActionContext
     */
    protected function getActionContext(array $context)
    {
        $data = [
            'entity' => null,
            'route' => null,
        ];

        if (isset($context['route'])) {
            $data['route'] = $context['route'];
        }

        if (isset($context['entityClass'])) {
            $data['entityClass'] = $context['entityClass'];
        }

        if (isset($context['entityId']) && isset($context['entityClass'])) {
            $data['entity'] = $this->findEntity($context['entityClass'], $context['entityId']);
        }

        return new ActionContext($data);
    }

//    protected $routeMap = [];
//    protected $entityMap = [];

    protected function isApplicable(ActionContext $context, Action $action)
    {
        if (!$action->getDefinition()->isEnabled()) {
            return false;
        }

        $route = $context->offsetGet('route');
        if ($route && in_array($route, $action->getDefinition()->getRoutes(), true)) {
            return true;
        }

        $class = $context->offsetGet('entityClass');
        if ($class && in_array($class, $action->getDefinition()->getEntities(), true)) {
            return true;
        }

        return false;
    }

    public function getActions(array $context)
    {
        $actionContext = $this->getActionContext($context);

        $actions = [];

        $configuration = $this->configurationProvider->getActionConfiguration();
        foreach ($configuration as $name => $parameters) {
            $definition = $this->assembleDefinition($name, $parameters);
            $action = $this->createActionModel($definition);

//            if ($definition->getRoutes()) {
//                foreach ($definition->getRoutes() as $route) {
//                    if (!isset($this->routeMap[$route])) {
//                        $this->routeMap[$route] = [];
//                    }
//                    $this->routeMap[$route][] = $action;
//
//                }
//                foreach ($definition->getEntities() as $entity) {
//                    if (!isset($this->entityMap[$entity])) {
//                        $this->entityMap[$entity] = [];
//                    }
//                    $this->entityMap[$entity][] = $action;
//
//                }
//            }

            // FILTER by route

            if (!$this->isApplicable($actionContext, $action)) {
                continue;
            }

            if (!$action->isAllowed($actionContext)) {
                continue;
            }

            $actions[] = $action;
        }

        return $actions;
    }
}
