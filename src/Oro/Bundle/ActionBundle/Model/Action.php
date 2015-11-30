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

    /** @var FunctionInterface[] */
    private $functions = [];

    /** @var AbstractCondition[] */
    private $conditions = [];

    /** @var AttributeManager[] */
    private $attributeManagers = [];

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
     * @param ActionContext $context
     */
    public function init(ActionContext $context)
    {
        $this->executeFunctions($context, ActionDefinition::INITFUNCTIONS);
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

        $this->executeFunctions($context, ActionDefinition::POSTFUNCTIONS);
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
        if ($this->hasForm()) {
            return $this->isPreConditionAllowed($context, $errors);
        } else {
            return $this->isAllowed($context, $errors);
        }
    }

    /**
     * Check is action allowed to execute
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
        $this->executeFunctions($context, ActionDefinition::PREFUNCTIONS);

        return $this->evaluateConditions($context, ActionDefinition::PRECONDITIONS, $errors);
    }

    /**
     * @param ActionContext $context
     * @param Collection $errors
     * @return bool
     */
    protected function isConditionAllowed(ActionContext $context, Collection $errors = null)
    {
        return $this->evaluateConditions($context, ActionDefinition::CONDITIONS, $errors);
    }

    /**
     * @param ActionContext $context
     * @return AttributeManager
     */
    public function getAttributeManager(ActionContext $context)
    {
        $hash = spl_object_hash($context);

        if (!array_key_exists($hash, $this->attributeManagers)) {
            $this->attributeManagers[$hash] = false;

            $config = $this->definition->getAttributes();
            if ($config) {
                $this->attributeManagers[$hash] = new AttributeManager(
                    $this->attributeAssembler->assemble($context, $config)
                );
            }
        }

        return $this->attributeManagers[$hash];
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
     * @param ActionContext $context
     * @param string $name
     */
    protected function executeFunctions(ActionContext $context, $name)
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
     * @param ActionContext $context
     * @param string $name
     * @param Collection $errors
     * @return boolean
     */
    protected function evaluateConditions(ActionContext $context, $name, Collection $errors = null)
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

    /**
     * @return bool
     */
    public function hasForm()
    {
        $formOptionsConfig = $this->definition->getFormOptions();

        return !empty($formOptionsConfig['attribute_fields']);
    }
}
