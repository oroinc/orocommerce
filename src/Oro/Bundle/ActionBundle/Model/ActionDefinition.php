<?php

namespace Oro\Bundle\ActionBundle\Model;

class ActionDefinition
{
    /** @var string */
    protected $name;

    /** @var string */
    protected $label;

    /** @var boolean */
    protected $enabled = true;

    /** @var array */
    protected $entities = [];

    /** @var array */
    protected $routes = [];

    /** @var array */
    protected $applications = [];

    /** @var integer */
    protected $order = 0;

    /** @var array */
    protected $frontendOptionsConfiguration;

    /** @var array */
    protected $formOptionsConfiguration;

    /** @var array */
    protected $attributesConfiguration;

    /** @var array */
    protected $preConditionsConfiguration;

    /** @var array */
    protected $conditionsConfiguration;

    /** @var array */
    protected $initStepConfiguration;

    /** @var array */
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
    public function getEnabled()
    {
        return $this->enabled;
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
    public function setEntities(array $entities)
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
    public function setApplications(array $applications)
    {
        $this->applications = $applications;

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
    public function setRoutes(array $routes)
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
    public function setFrontendOptionsConfiguration(array $frontendOptionsConfiguration)
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
    public function setFormOptionsConfiguration(array $formOptionsConfiguration)
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
    public function setAttributesConfiguration(array $attributesConfiguration)
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
    public function setPreConditionsConfiguration(array $preConditionsConfiguration)
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
    public function setConditionsConfiguration(array $conditionsConfiguration)
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
    public function setInitStepConfiguration(array $initStepConfiguration)
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
    public function setExecutionStepConfiguration(array $executionStepConfiguration)
    {
        $this->executionStepConfiguration = $executionStepConfiguration;

        return $this;
    }
}
