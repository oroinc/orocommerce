<?php

namespace Oro\Bundle\ActionBundle\Model;

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
    private $routes = [];

    /**
     * @var array
     */
    private $entities = [];

    /**
     * @var bool
     */
    private $loaded = false;

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
     * @return Action[]
     */
    public function getActions(array $context)
    {
        $this->normalizeContext($context);

        $actionContext = $this->createActionContext($context);

        $this->loadActions($actionContext);

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
    protected function loadActions(ActionContext $actionContext)
    {
        if ($this->loaded) {
            return;
        }

        $configuration = $this->configurationProvider->getActionConfiguration();

        $actions = $this->assembler->assemble($configuration);

        foreach ($actions as $action) {
            $action->init($actionContext);

            $this->mapActionRoutes($action);
            $this->mapActionEntities($action);
        }

        $this->loaded = true;
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
}
