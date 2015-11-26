<?php

namespace Oro\Bundle\ActionBundle\Model;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\ActionBundle\Exception\ForbiddenActionException;
use Oro\Bundle\WorkflowBundle\Model\Action\ActionFactory as FunctionFactory;
use Oro\Bundle\WorkflowBundle\Model\Action\ActionInterface as FunctionInterface;
use Oro\Bundle\WorkflowBundle\Model\Action\Configurable as ConfigurableAction;

use Oro\Bundle\WorkflowBundle\Model\AttributeManager;
use Oro\Bundle\WorkflowBundle\Model\Condition\AbstractCondition;
use Oro\Bundle\WorkflowBundle\Model\Condition\Configurable as ConfigurableCondition;

use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

class Action
{
    /** @var FunctionFactory */
    private $functionFactory;

    /** @var ConditionFactory */
    private $conditionFactory;

    /** @var AttributeAssembler */
    private $attributeAssembler;

    /** @var FormOptionsAssembler */
    private $formOptionsAssembler;

    /** @var ActionDefinition */
    private $definition;

    /** @var FunctionInterface */
    private $preFunction;

    /** @var AbstractCondition */
    private $preCondition;

    /** @var AbstractCondition */
    private $condition;

    /** @var FunctionInterface */
    private $postFunction;

    /** @var array */
    private $formOptions;

    /**
     * @param FunctionFactory $functionFactory
     * @param ConditionFactory $conditionFactory
     * @param AttributeAssembler $attributeAssembler
     * @param ActionDefinition $definition
     */
    public function __construct(
        FunctionFactory $functionFactory,
        ConditionFactory $conditionFactory,
        AttributeAssembler $attributeAssembler,
        FormOptionsAssembler $formOptionsAssembler,
        ActionDefinition $definition
    ) {
        $this->functionFactory = $functionFactory;
        $this->conditionFactory = $conditionFactory;
        $this->attributeAssembler = $attributeAssembler;
        $this->formOptionsAssembler = $formOptionsAssembler;
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
     * @return FunctionInterface
     */
    protected function getPostFunctions()
    {
        if ($this->postFunction === null) {
            $this->postFunction = false;
            $postFunctionsConfig = $this->definition->getPostFunctions();
            if ($postFunctionsConfig) {
                $this->postFunction = $this->functionFactory->create(ConfigurableAction::ALIAS, $postFunctionsConfig);
            }
        }

        return $this->postFunction;
    }

    /**
     * @param ActionContext $context
     * @return array
     */
    public function getFormOptions(ActionContext $context)
    {
        if ($this->formOptions === null) {
            $this->formOptions = false;
            $formOptionsConfig = $this->definition->getFormOptions();
            if ($formOptionsConfig) {
                $this->formOptions = $this->formOptionsAssembler
                    ->assemble($formOptionsConfig, $this->getAttributeManager($context)->getAttributes());
            }
        }

        return $this->formOptions;
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
     */
    public function init(ActionContext $context)
    {
        // ToDo - implement init
    }

    /**
     * @param ActionContext $context
     * @return AttributeManager
     */
    public function getAttributeManager(ActionContext $context)
    {
        return new AttributeManager(
            $this->attributeAssembler->assemble(
                $context,
                $this->definition->getAttributes()
            )
        );
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

        if ($this->getPostFunctions()) {
            $this->getPostFunctions()->execute($context);
        }
    }

    /**
     * @param ActionContext $context
     * @param Collection $errors
     * @return bool
     */
    protected function isPreConditionAllowed(ActionContext $context, Collection $errors = null)
    {
        if ($this->getPreFunctions()) {
            $this->getPreFunctions()->execute($context);
        }

        if ($this->getPreCondition()) {
            return $this->getPreCondition()->evaluate($context, $errors);
        }

        return true;
    }

    /**
     * @param ActionContext $context
     * @param Collection $errors
     * @return bool
     */
    protected function isConditionAllowed(ActionContext $context, Collection $errors = null)
    {
        if ($this->getCondition()) {
            return $this->getCondition()->evaluate($context, $errors);
        }

        return true;
    }
}
