<?php

namespace Oro\Bundle\ActionBundle\Model;

class ActionDefinition
{
    const EXTEND_STRATEGY_ADD = 'add';

    const EXTEND_STRATEGY_REPLACE = 'create';

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var boolean
     */
    protected $enabled = true;

    /**
     * @var array
     */
    protected $entities;

    /**
     * @var array
     */
    protected $routes;

    /**
     * @var array
     */
    protected $applications;

    /**
     * @var string
     */
    protected $extend;

    /**
     * @var string
     */
    protected $extendStrategy = self::EXTEND_STRATEGY_REPLACE;

    /**
     * @var integer
     */
    protected $order = 0;

    /**
     * @var array
     */
    protected $frontendOptionsConfiguration;

    /**
     * @var array
     */
    protected $formOptionsConfiguration;

    /**
     * @var array
     */
    protected $attributesConfiguration;

    /**
     * @var array
     */
    protected $preConditionsConfiguration;

    /**
     * @var array
     */
    protected $conditionsConfiguration;

    /**
     * @var array
     */
    protected $initStepConfiguration;

    /**
     * @var array
     */
    protected $executionStepConfiguration;

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $label
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param boolean $enabled
     * @return $this
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param integer $order
     * @return $this
     */
    public function setOrder($order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * @return integer
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @return array
     */
    public function getEntities()
    {
        return $this->entities;
    }

    /**
     * @param array $entities
     * @return $this
     */
    public function setEntities($entities)
    {
        $this->entities = $entities;

        return $this;
    }

    /**
     * @return array
     */
    public function getApplications()
    {
        return $this->applications;
    }

    /**
     * @param array $applications
     * @return $this
     */
    public function setApplications($applications)
    {
        $this->applications = $applications;

        return $this;
    }

    /**
     * @return string
     */
    public function getExtend()
    {
        return $this->extend;
    }

    /**
     * @param string $extend
     * @return $this
     */
    public function setExtend($extend)
    {
        $this->extend = $extend;

        return $this;
    }

    /**
     * @return string
     */
    public function getExtendStrategy()
    {
        return $this->extendStrategy;
    }

    /**
     * @param string $extendStrategy
     * @return $this
     */
    public function setExtendStrategy($extendStrategy)
    {
        $this->extendStrategy = $extendStrategy;

        return $this;
    }

    /**
     * @return array
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * @param array $routes
     * @return $this
     */
    public function setRoutes($routes)
    {
        $this->routes = $routes;

        return $this;
    }

    /**
     * @return array
     */
    public function getFrontendOptionsConfiguration()
    {
        return $this->frontendOptionsConfiguration;
    }

    /**
     * @param array $frontendOptionsConfiguration
     * @return $this
     */
    public function setFrontendOptionsConfiguration($frontendOptionsConfiguration)
    {
        $this->frontendOptionsConfiguration = $frontendOptionsConfiguration;

        return $this;
    }

    /**
     * @return array
     */
    public function getFormOptionsConfiguration()
    {
        return $this->formOptionsConfiguration;
    }

    /**
     * @param array $formOptionsConfiguration
     * @return $this
     */
    public function setFormOptionsConfiguration($formOptionsConfiguration)
    {
        $this->formOptionsConfiguration = $formOptionsConfiguration;

        return $this;
    }

    /**
     * @return array
     */
    public function getAttributesConfiguration()
    {
        return $this->attributesConfiguration;
    }

    /**
     * @param array $attributesConfiguration
     * @return $this
     */
    public function setAttributesConfiguration($attributesConfiguration)
    {
        $this->attributesConfiguration = $attributesConfiguration;

        return $this;
    }

    /**
     * @return array
     */
    public function getPreConditionsConfiguration()
    {
        return $this->preConditionsConfiguration;
    }

    /**
     * @param array $preConditionsConfiguration
     * @return $this
     */
    public function setPreConditionsConfiguration($preConditionsConfiguration)
    {
        $this->preConditionsConfiguration = $preConditionsConfiguration;

        return $this;
    }

    /**
     * @return array
     */
    public function getConditionsConfiguration()
    {
        return $this->conditionsConfiguration;
    }

    /**
     * @param array $conditionsConfiguration
     * @return $this
     */
    public function setConditionsConfiguration($conditionsConfiguration)
    {
        $this->conditionsConfiguration = $conditionsConfiguration;

        return $this;
    }

    /**
     * @return array
     */
    public function getInitStepConfiguration()
    {
        return $this->initStepConfiguration;
    }

    /**
     * @param array $initStepConfiguration
     * @return $this
     */
    public function setInitStepConfiguration($initStepConfiguration)
    {
        $this->initStepConfiguration = $initStepConfiguration;

        return $this;
    }

    /**
     * @return array
     */
    public function getExecutionStepConfiguration()
    {
        return $this->executionStepConfiguration;
    }

    /**
     * @param array $executionStepConfiguration
     * @return $this
     */
    public function setExecutionStepConfiguration($executionStepConfiguration)
    {
        $this->executionStepConfiguration = $executionStepConfiguration;

        return $this;
    }
}
