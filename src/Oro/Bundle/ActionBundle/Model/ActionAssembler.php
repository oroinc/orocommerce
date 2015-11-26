<?php

namespace Oro\Bundle\ActionBundle\Model;

use Oro\Bundle\WorkflowBundle\Model\Action\ActionFactory as FunctionFactory;
use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

class ActionAssembler extends AbstractAssembler
{
    /** @var FunctionFactory */
    private $functionFactory;

    /** @var ConditionFactory */
    private $conditionFactory;

    /** @var AttributeAssembler */
    private $attributeAssembler;

    /** @var FormOptionsAssembler */
    private $formOptionsAssembler;

    /**
     * @param FunctionFactory $functionFactory
     * @param ConditionFactory $conditionFactory
     * @param AttributeAssembler $attributeAssembler
     */
    public function __construct(
        FunctionFactory $functionFactory,
        ConditionFactory $conditionFactory,
        AttributeAssembler $attributeAssembler,
        FormOptionsAssembler $formOptionsAssembler
    ) {
        $this->functionFactory = $functionFactory;
        $this->conditionFactory = $conditionFactory;
        $this->attributeAssembler = $attributeAssembler;
        $this->formOptionsAssembler = $formOptionsAssembler;
    }

    /**
     * @param array $configuration
     * @return Action[]
     */
    public function assemble(array $configuration)
    {
        $actions = [];

        foreach ($configuration as $actionName => $options) {
            $actions[$actionName] = new Action(
                $this->functionFactory,
                $this->conditionFactory,
                $this->attributeAssembler,
                $this->formOptionsAssembler,
                $this->assembleDefinition($actionName, $options)
            );
        }

        return $actions;
    }

    /**
     * @param string $actionName
     * @param array $options
     * @return ActionDefinition
     */
    protected function assembleDefinition($actionName, array $options)
    {
        $this->assertOptions($options, ['label']);
        $actionDefinition = new ActionDefinition();

        $actionDefinition
            ->setName($actionName)
            ->setLabel($this->getOption($options, 'label'))
            ->setEntities($this->getOption($options, 'entities', []))
            ->setRoutes($this->getOption($options, 'routes', []))
            ->setApplications($this->getOption($options, 'applications', []))
            ->setEnabled($this->getOption($options, 'enabled', true))
            ->setOrder($this->getOption($options, 'order', 0))
            ->setFrontendOptions($this->getOption($options, 'frontend_options', []))
            ->setAttributes($this->getOption($options, 'attributes', []))
            ->setFormOptions($this->getOption($options, 'form_options', []))
            ->setPreFunctions($this->getOption($options, 'prefunctions', []))
            ->setPreConditions($this->getOption($options, 'preconditions', []))
            ->setConditions($this->getOption($options, 'conditions', []))
            ->setPostFunctions($this->getOption($options, 'postfunctions', []))
            ->setInitStep($this->getOption($options, 'init_step', []))
            ->setExecutionStep($this->getOption($options, 'execution_step', []));

        return $actionDefinition;
    }
}
