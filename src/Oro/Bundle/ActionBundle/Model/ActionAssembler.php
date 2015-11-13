<?php

namespace Oro\Bundle\ActionBundle\Model;

use Oro\Bundle\ActionBundle\Exception\MissedRequiredOptionException;

use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

class ActionAssembler
{
    /** @var ConditionFactory */
    private $conditionFactory;

    /**
     * @param ConditionFactory $conditionFactory
     */
    public function __construct(ConditionFactory $conditionFactory)
    {
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
            $actions[$actionName] = new Action($this->conditionFactory, $definition);
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
            ->setPreConditions($this->getOption($options, 'pre_conditions', []))
            ->setConditions($this->getOption($options, 'conditions', []))
            ->setInitStep($this->getOption($options, 'init_step', []))
            ->setExecutionStep($this->getOption($options, 'execution_step', []));

        return $actionDefinition;
    }

    /**
     * @param array $options
     * @param array $requiredOptions
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
