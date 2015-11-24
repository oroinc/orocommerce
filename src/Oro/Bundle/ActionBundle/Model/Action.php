<?php

namespace Oro\Bundle\ActionBundle\Model;

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

    /** @var FunctionInterface */
    private $preFunction;

    /** @var AbstractCondition */
    private $preCondition;

    /** @var AbstractCondition */
    private $condition;

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
     * @return FunctionInterface
     */
    protected function getPreFunctions()
    {
        if ($this->preFunction === null) {
            $this->preFunction = false;
            $preFunctionsConfig = $this->definition->getPreFunctions();
            if ($preFunctionsConfig) {
                $this->preFunction = $this->functionFactory->create(ConfigurableAction::ALIAS, $preFunctionsConfig);
            }
        }

        return $this->preFunction;
    }

    /**
     * @return AbstractCondition
     */
    protected function getPreCondition()
    {
        if ($this->preCondition === null) {
            $this->preCondition = false;
            $preConditionsConfig = $this->definition->getPreConditions();
            if ($preConditionsConfig) {
                $this->preCondition = $this->conditionFactory
                    ->create(ConfigurableCondition::ALIAS, $preConditionsConfig);
            }
        }

        return $this->preCondition;
    }

    /**
     * @return AbstractCondition
     */
    protected function getCondition()
    {
        if ($this->condition === null) {
            $this->condition = false;
            $conditionConfig = $this->definition->getConditions();
            if ($conditionConfig) {
                $this->condition = $this->conditionFactory->create(ConfigurableCondition::ALIAS, $conditionConfig);
            }
        }

        return $this->condition;
    }

    /**
     * @param ActionContext $context
     */
    public function init(ActionContext $context)
    {
        // ToDo - implement init
    }

    /**
     * @param ActionContext $context
     */
    public function execute(ActionContext $context)
    {
        // ToDo - implement execution
    }

    /**
     * @param ActionContext $context
     * @return bool
     */
    public function isPreConditionAllowed(ActionContext $context)
    {
        if ($this->getPreFunctions()) {
            $this->getPreFunctions()->execute($context);
        }

        if ($this->getPreCondition()) {
            return $this->getPreCondition()->evaluate($context);
        }

        return true;
    }

    /**
     * @param ActionContext $context
     * @return bool
     */
    public function isConditionAllowed(ActionContext $context)
    {
        if ($this->getCondition()) {
            return $this->getCondition()->evaluate($context);
        }

        return true;
    }
}
