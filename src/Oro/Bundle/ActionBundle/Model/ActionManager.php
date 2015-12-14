<?php

namespace Oro\Bundle\ActionBundle\Model;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\ActionBundle\Configuration\ActionConfigurationProvider;
use Oro\Bundle\ActionBundle\Exception\ActionNotFoundException;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class ActionManager
{
    const DEFAULT_DIALOG_TEMPLATE = 'OroActionBundle:Widget:widget/form.html.twig';

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var ContextHelper
     */
    protected $contextHelper;

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
     * @var array
     */
    private $datagrids;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ContextHelper $contextHelper
     * @param ActionConfigurationProvider $configurationProvider
     * @param ActionAssembler $assembler
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ContextHelper $contextHelper,
        ActionConfigurationProvider $configurationProvider,
        ActionAssembler $assembler
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->contextHelper = $contextHelper;
        $this->configurationProvider = $configurationProvider;
        $this->assembler = $assembler;
    }

    /**
     * @param string $actionName
     * @param ActionContext $actionContext
     * @param Collection $errors
     * @return ActionContext
     * @throws \Exception
     */
    public function execute($actionName, ActionContext $actionContext = null, Collection $errors = null)
    {
        $action = $this->getAction($actionName);
        if (!$action) {
            throw new ActionNotFoundException($actionName);
        }

        if (!$actionContext) {
            $actionContext = $this->contextHelper->getActionContext();
        }
        $action->execute($actionContext, $errors);

        $entity = $actionContext->getEntity();
        if ($entity) {
            $manager = $this->doctrineHelper->getEntityManager($entity);
            $manager->beginTransaction();

            try {
                $manager->flush();
                $manager->commit();
            } catch (\Exception $e) {
                $manager->rollback();
                throw $e;
            }
        }

        return $actionContext;
    }

    /**
     * @param array|null $context
     * @return bool
     */
    public function hasActions(array $context = null)
    {
        return count($this->getActions($context)) > 0;
    }

    /**
     * @param array|null $context
     * @return Action[]
     */
    public function getActions(array $context = null)
    {
        $this->loadActions();

        return $this->findActions($context === null ? $this->contextHelper->getContext() : $context);
    }

    /**
     * @param string $actionName
     * @param array|null $context
     * @return null|Action
     */
    public function getAction($actionName, array $context = null)
    {
        $actions = $this->getActions($context);

        return array_key_exists($actionName, $actions) ? $actions[$actionName] : null;
    }

    /**
     * @param string $actionName
     * @return string
     */
    public function getDialogTemplate($actionName)
    {
        $template = self::DEFAULT_DIALOG_TEMPLATE;
        $action = $this->getAction($actionName);

        if ($action) {
            $frontendOptions = $action->getDefinition()->getFrontendOptions();

            if (array_key_exists('dialog_template', $frontendOptions)) {
                $template = $frontendOptions['dialog_template'];
            }
        }

        return $template;
    }

    /**
     * @param array|null $context
     * @return Action[]
     */
    protected function findActions(array $context = null)
    {
        /** @var $actions Action[] */
        $actions = [];

        $context = array_merge($this->contextHelper->getContext(), (array)$context);

        if ($context['route'] && array_key_exists($context['route'], $this->routes)) {
            $actions = $this->routes[$context['route']];
        }

        if ($context['datagrid'] && array_key_exists($context['datagrid'], $this->datagrids)) {
            $actions = $this->datagrids[$context['datagrid']];
        }

        if ($context['entityClass'] &&
            $context['entityId'] &&
            array_key_exists($context['entityClass'], $this->entities)
        ) {
            $actions = array_merge($actions, $this->entities[$context['entityClass']]);
        }

        $actionContext = $this->contextHelper->getActionContext($context);
        $actions = array_filter($actions, function (Action $action) use ($actionContext) {
            return $action->isEnabled() && $action->isAvailable($actionContext);
        });

        uasort($actions, function (Action $action1, Action $action2) {
            return $action1->getDefinition()->getOrder() - $action2->getDefinition()->getOrder();
        });

        return $actions;
    }

    protected function loadActions()
    {
        if ($this->entities !== null || $this->routes !== null || $this->datagrids !== null) {
            return;
        }

        $this->routes = [];
        $this->entities = [];
        $this->datagrids = [];

        $configuration = $this->configurationProvider->getActionConfiguration();
        $actions = $this->assembler->assemble($configuration);

        foreach ($actions as $action) {
            $this->mapActionRoutes($action);
            $this->mapActionEntities($action);
            $this->mapActionDatagrids($action);
        }
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
     * @param Action $action
     */
    protected function mapActionDatagrids(Action $action)
    {
        foreach ($action->getDefinition()->getDatagrids() as $datagridName) {
            $this->datagrids[$datagridName][$action->getName()] = $action;
        }
    }

    /**
     * @param string $entityName
     * @return string|bool
     */
    protected function getEntityClassName($entityName)
    {
        try {
            $entityClass = $this->doctrineHelper->getEntityClass($entityName);

            if (!class_exists($entityClass, true)) {
                return false;
            }

            $reflection = new \ReflectionClass($entityClass);

            return $reflection->getName();
        } catch (\Exception $e) {
            return false;
        }
    }
}
