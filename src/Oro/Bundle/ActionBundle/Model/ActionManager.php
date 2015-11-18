<?php

namespace Oro\Bundle\ActionBundle\Model;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use Oro\Bundle\ActionBundle\Configuration\ActionConfigurationProvider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

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
     * @var ActionAssembler
     */
    protected $assembler;

    /**
     * @var array
     */
    private $routes;

    /**
     * @var array
     */
    private $entities;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ActionConfigurationProvider $configurationProvider
     * @param ActionAssembler $assembler
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ActionConfigurationProvider $configurationProvider,
        ActionAssembler $assembler
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->configurationProvider = $configurationProvider;
        $this->assembler = $assembler;
    }

    /**
     * @param array $context
     * @return bool
     */
    public function hasActions(array $context)
    {
        return count($this->getActions($context)) > 0;
    }

    /**
     * @param array $context
     * @return Action[]
     */
    public function getActions(array $context)
    {
        $this->loadActions();

        $context = $this->normalizeContext($context);

        return $this->findActions($context);
    }

    /**
     * @param array $context
     * @return Action[]
     */
    protected function findActions(array $context)
    {
        /** @var $actions Action[] */
        $actions = [];

        $actionContext = $this->createActionContext($context);

        if ($context['route'] && array_key_exists($context['route'], $this->routes)) {
            $actions = array_merge($actions, $this->routes[$context['route']]);
        }

        if ($context['entityClass'] &&
            $context['entityId'] &&
            array_key_exists($context['entityClass'], $this->entities)
        ) {
            $actions = array_merge($actions, $this->entities[$context['entityClass']]);
        }

        $actions = array_filter($actions, function (Action $action) use ($actionContext) {
            return $action->isEnabled() && $action->isAllowed($actionContext);
        });

        uasort($actions, function (Action $action1, Action $action2) {
            return $action1->getDefinition()->getOrder() - $action2->getDefinition()->getOrder();
        });

        return $actions;
    }

    protected function loadActions()
    {
        if ($this->entities !== null || $this->routes !== null) {
            return;
        }

        $this->routes = [];
        $this->entities = [];

        $configuration = $this->configurationProvider->getActionConfiguration();
        $actions = $this->assembler->assemble($configuration);

        foreach ($actions as $action) {
            $this->mapActionRoutes($action);
            $this->mapActionEntities($action);
        }
    }

    /**
     * @param array $context
     * @return ActionContext
     */
    protected function createActionContext(array $context)
    {
        $entity = null;

        if ($context['entityClass']) {
            $entity = $this->getEntityReference($context['entityClass'], $context['entityId']);
        }

        return new ActionContext($entity ? ['entity' => $entity] : []);
    }

    /**
     * @param Action $action
     */
    protected function mapActionRoutes(Action $action)
    {
        foreach ($action->getDefinition()->getRoutes() as $routeName) {
            $this->routes[$routeName][$action->getName()] = $action;
        }
    }

    /**
     * @param Action $action
     */
    protected function mapActionEntities(Action $action)
    {
        foreach ($action->getDefinition()->getEntities() as $entityName) {
            if (false === ($className = $this->getEntityClassName($entityName))) {
                continue;
            }
            $this->entities[$className][$action->getName()] = $action;
        }
    }

    /**
     * @param string $entityName
     * @return string|false
     */
    protected function getEntityClassName($entityName)
    {
        $entityClass = $this->doctrineHelper->getEntityClass($entityName);

        return class_exists($entityClass, true) ? $entityClass : false;
    }

    /**
     * @param string $entityClass
     * @param mixed $entityId
     * @return Object
     * @throws BadRequestHttpException
     */
    protected function getEntityReference($entityClass, $entityId)
    {
        $entity = null;

        if ($this->doctrineHelper->isManageableEntity($entityClass)) {
            if ($entityId) {
                $entity = $this->doctrineHelper->getEntityReference($entityClass, $entityId);
            } else {
                $entity = $this->doctrineHelper->createEntityInstance($entityClass);
            }
        }

        return $entity;
    }

    /**
     * @param array $context
     * @return array
     */
    protected function normalizeContext(array $context)
    {
        return array_merge([
            'route' => null,
            'entityId' => null,
            'entityClass' => null,
        ], $context);
    }
}
