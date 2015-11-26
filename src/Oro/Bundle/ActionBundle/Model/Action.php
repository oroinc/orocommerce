<?php

namespace Oro\Bundle\ActionBundle\Model;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\ActionBundle\Exception\ForbiddenActionException;
use Oro\Bundle\WorkflowBundle\Model\Action\ActionFactory as FunctionFactory;
use Oro\Bundle\WorkflowBundle\Model\Action\ActionInterface as FunctionInterface;
use Oro\Bundle\WorkflowBundle\Model\Action\Configurable as ConfigurableAction;

use Oro\Bundle\WorkflowBundle\Model\Condition\AbstractCondition;
use Oro\Bundle\WorkflowBundle\Model\Condition\Configurable as ConfigurableCondition;

use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

class Action
{
    /** @var FunctionFactory */
    private $functionFactory;

    /** @var ConditionFactory */
    private $conditionFactory;

    /** @var ActionDefinition */
    private $definition;

    /** @var FunctionInterface[] */
    private $functions = [];

    /** @var AbstractCondition[] */
    private $conditions = [];

    /**
     * @param FunctionFactory $functionFactory
     * @param ConditionFactory $conditionFactory
     * @param ActionDefinition $definition
     */
    public function __construct(
        FunctionFactory $functionFactory,
        ConditionFactory $conditionFactory,
        ActionDefinition $definition
    ) {
        $this->functionFactory = $functionFactory;
        $this->conditionFactory = $conditionFactory;
        $this->definition = $definition;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->getDefinition()->isEnabled();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getDefinition()->getName();
    }

    /**
     * @return ActionDefinition
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * @param ActionContext $context
     */
    public function init(ActionContext $context)
    {
        $this->executeFunctions(ActionDefinition::INITFUNCTIONS, $context);
    }

    /**
     * @param ActionContext $context
     * @param Collection $errors
     * @throws ForbiddenActionException
     */
    public function execute(ActionContext $context, Collection $errors = null)
    {
        if (!$this->isAllowed($context, $errors)) {
            throw new ForbiddenActionException(sprintf('Action "%s" is not allowed.', $this->getName()));
        }

        $this->executeFunctions(ActionDefinition::POSTFUNCTIONS, $context);
    }

    /**
     * Check that action is available to show
     *
     * @param ActionContext $context
     * @param Collection $errors
     * @return bool
     */
    public function isAvailable(ActionContext $context, Collection $errors = null)
    {
        return $this->isPreConditionAllowed($context, $errors);
    }

    /**
     * Check is transition allowed to execute
     *
     * @param ActionContext $context
     * @param Collection|null $errors
     * @return bool
     */
    public function isAllowed(ActionContext $context, Collection $errors = null)
    {
        return $this->isPreConditionAllowed($context, $errors) && $this->isConditionAllowed($context, $errors);
    }

    /**
     * @param ActionContext $context
     * @param Collection $errors
     * @return bool
     */
    protected function isPreConditionAllowed(ActionContext $context, Collection $errors = null)
    {
        $this->executeFunctions(ActionDefinition::PREFUNCTIONS, $context);

        return $this->evaluateConditions(ActionDefinition::PRECONDITIONS, $context, $errors);
    }

    /**
     * @param ActionContext $context
     * @param Collection $errors
     * @return bool
     */
    protected function isConditionAllowed(ActionContext $context, Collection $errors = null)
    {
        return $this->evaluateConditions(ActionDefinition::CONDITIONS, $context, $errors);
    }

    /**
     * @param string $name
     * @param ActionContext $context
     */
    protected function executeFunctions($name, ActionContext $context)
    {
        if (!array_key_exists($name, $this->functions)) {
            $this->functions[$name] = false;

            $config = $this->definition->getFunctions($name);
            if ($config) {
                $this->functions[$name] = $this->functionFactory->create(ConfigurableAction::ALIAS, $config);
            }
        }

        if ($this->functions[$name] instanceof FunctionInterface) {
            $this->functions[$name]->execute($context);
        }
    }

    /**
     * @param string $name
     * @param ActionContext $context
     * @param Collection $errors
     * @return boolean
     */
    protected function evaluateConditions($name, ActionContext $context, Collection $errors = null)
    {
        if (!array_key_exists($name, $this->conditions)) {
            $this->conditions[$name] = false;

            $config = $this->definition->getConditions($name);
            if ($config) {
                $this->conditions[$name] = $this->conditionFactory->create(ConfigurableCondition::ALIAS, $config);
            }
        }

        if ($this->conditions[$name] instanceof ConfigurableCondition) {
            return $this->conditions[$name]->evaluate($context, $errors);
        }

        return true;
    }
}
