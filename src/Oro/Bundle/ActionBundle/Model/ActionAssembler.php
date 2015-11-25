<?php

namespace Oro\Bundle\ActionBundle\Model;

use Oro\Bundle\ActionBundle\Exception\MissedRequiredOptionException;

use Oro\Bundle\WorkflowBundle\Model\Action\ActionFactory as FunctionFactory;
use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

class ActionAssembler
{
    /** @var FunctionFactory */
    private $functionFactory;

    /** @var ConditionFactory */
    private $conditionFactory;

    /**
     * @param FunctionFactory $functionFactory
     * @param ConditionFactory $conditionFactory
     */
    public function __construct(FunctionFactory $functionFactory, ConditionFactory $conditionFactory)
    {
        $this->functionFactory = $functionFactory;
        $this->conditionFactory = $conditionFactory;
    }

    /**
     * @param array $configuration
     * @return Action[]
     */
    public function assemble(array $configuration)
    {
        $actions = [];

        foreach ($configuration as $actionName => $options) {
            $definition = $this->assembleDefinition($actionName, $options);
            $actions[$actionName] = new Action($this->functionFactory, $this->conditionFactory, $definition);
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
            ->setInitStep($this->getOption($options, 'init_step', []))
            ->setExecutionStep($this->getOption($options, 'execution_step', []));

        foreach (ActionDefinition::getAllowedConditions() as $name) {
            $actionDefinition->addConditions($name, $this->getOption($options, $name, []));
        }

        foreach (ActionDefinition::getAllowedFunctions() as $name) {
            $actionDefinition->addFunctions($name, $this->getOption($options, $name, []));
        }

        return $actionDefinition;
    }

    /**
     * @param array $options
     * @param array $requiredOptions
     * @throws MissedRequiredOptionException
     */
    protected function assertOptions(array $options, array $requiredOptions)
    {
        foreach ($requiredOptions as $optionName) {
            if (empty($options[$optionName])) {
                throw new MissedRequiredOptionException(sprintf('Option "%s" is required', $optionName));
            }
        }
    }

    /**
     * @param array $options
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getOption(array $options, $key, $default = null)
    {
        if (array_key_exists($key, $options)) {
            return $options[$key];
        }

        return $default;
    }
}
