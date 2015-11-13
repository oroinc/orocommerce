<?php

namespace Oro\Bundle\ActionBundle\Model;

use Oro\Bundle\ActionBundle\Configuration\ActionConfigurationProvider;
use Oro\Bundle\ActionBundle\Model\ActionDefinition;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

class ActionManager
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var ActionConfigurationProvider
     */
    protected $configurationProvider;

    /**
     * @var ConditionFactory
     */
    protected $conditionFactory;

    /**
     * @var array
     */
    private $routes;

    /**
     * @var array
     */
    private $entities = [];

    /**
     * @var Action[]
     */
    private $actions;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ActionConfigurationProvider $configurationProvider
     * @param ConditionFactory $conditionFactory
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ActionConfigurationProvider $configurationProvider,
        ConditionFactory $conditionFactory
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->configurationProvider = $configurationProvider;
        $this->conditionFactory = $conditionFactory;
    }

    /**
     * @param array $context
     * @return Action[]
     */
    public function getActions(array $context)
    {
        $this->normalizeContext($context);

        $actionContext = $this->createActionContext($context);

        if (!$this->actions) {
            $this->prepareActions($actionContext);
        }

        return $this->findActions($context, $actionContext);
    }

    /**
     * @param array $context
     * @return Action[]
     */
    protected function findActions(array $context, ActionContext $actionContext)
    {
        /* @var $actions Action */
        $actions = [];

        if ($context['route']) {
            if (array_key_exists($context['route'], $this->routes)) {
                $actions = array_merge($actions, $this->routes[$context['route']]);
            }
        }

        if ($context['entityClass'] && $context['entityId']) {
            if (array_key_exists($context['entityClass'], $this->entities)) {
                $actions = array_merge($actions, $this->entities[$context['entityClass']]);
            }
        }

        $actions = array_filter($actions, function (Action $action) use ($actionContext) {
            return $action->isEnabled() && $action->isAllowed($actionContext);
        });

        uasort($actions, function (Action $action1, Action $action2) {
            return $action1->getDefinition()->getOrder() - $action2->getDefinition()->getOrder();
        });

        return $actions;
    }

    /**
     * @param ActionContext $actionContext
     */
    protected function prepareActions(ActionContext $actionContext)
    {
        $configuration = $this->configurationProvider->getActionConfiguration();
        foreach ($configuration as $name => $parameters) {
            $definition = $this->assembleDefinition($name, $parameters);
            $action = $this->createAction($definition);
            $action->init($actionContext);

            $this->mapActionRoutes($action);
            $this->mapActionEntities($action);

            $this->actions[] = $action;
        }
    }

    /**
     * @param string $name
     * @param array $config
     * @return ActionDefinition
     */
    protected function assembleDefinition($name, array $config)
    {
        // TODO: use assembler
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

    /**
     * @param ActionDefinition $definition
     * @return Action
     */
    protected function createAction(ActionDefinition $definition)
    {
        $action = new Action($this->conditionFactory, $definition);
        $action
            ->setEnabled($definition->getEnabled())
            ->setName($definition->getName());

        return $action;
    }

    /**
     * @param array $context
     * @return ActionContext
     */
    protected function createActionContext(array $context)
    {
        $data = [];

        if ($context['entityClass']) {
            $data['entity'] = $this->getEntityReference(
                $context['entityClass'],
                $context['entityId']
            );
        }

        return new ActionContext($data);
    }

    /**
     * @param Action $action
     */
    protected function mapActionRoutes(Action $action)
    {
        foreach ($action->getDefinition()->getRoutes() as $routeName) {
            if (!isset($this->routes[$routeName])) {
                $this->routes[$routeName] = [];
            }
            $this->routes[$routeName][$action->getName()] = $action;
        }
    }

    /**
     * @param Action $action
     */
    protected function mapActionEntities(Action $action)
    {
        foreach ($action->getDefinition()->getEntities() as $entityName) {
            if (!isset($this->entities[$entityName])) {
                $this->entities[$entityName] = [];
            }
            $this->entities[$entityName][$action->getName()] = $action;
        }
    }

    /**
     * @param string $entityClass
     * @param mixed $entityId
     * @return Object
     * @throws BadRequestHttpException
     */
    protected function getEntityReference($entityClass, $entityId)
    {
        try {
            if ($entityId) {
                $entity = $this->doctrineHelper->getEntityReference($entityClass, $entityId);
            } else {
                $entity = $this->doctrineHelper->createEntityInstance($entityClass);
            }
        } catch (NotManageableEntityException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }

        return $entity;
    }

    /**
     * @param array $context
     */
    protected function normalizeContext(array &$context)
    {
        $context = array_merge([
            'route' => null,
            'entityId' => null,
            'entityClass' => null,
        ], $context);
    }

    /**
     * @param array $context
     * @return bool
     */
    public function hasActions(array $context)
    {
        return count($this->getActions($context)) > 0;
    }
}
