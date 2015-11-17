<?php

namespace Oro\Bundle\ActionBundle\Model;

class ActionDefinition
{
    /** @var string */
    private $name;

    /** @var string */
    private $label;

    /** @var boolean */
    private $enabled = true;

    /** @var array */
    private $entities = [];

    /** @var array */
    private $routes = [];

    /** @var array */
    private $applications = [];

    /** @var integer */
    private $order = 0;

    /** @var array */
    private $frontendOptions = [];

    /** @var array */
    private $formOptions = [];

    /** @var array */
    private $attributes = [];

    /** @var array */
    private $preConditions = [];

    /** @var array */
    private $conditions = [];

    /** @var array */
    private $initStep = [];

    /** @var array */
    private $executionStep = [];

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
    public function getFrontendOptions()
    {
        return $this->frontendOptions;
    }

    /**
     * @param array $frontendOptions
     * @return $this
     */
    public function setFrontendOptions(array $frontendOptions)
    {
        $this->frontendOptions = $frontendOptions;

        return $this;
    }

    /**
     * @return array
     */
    public function getFormOptions()
    {
        return $this->formOptions;
    }

    /**
     * @param array $formOptions
     * @return $this
     */
    public function setFormOptions(array $formOptions)
    {
        $this->formOptions = $formOptions;

        return $this;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param array $attributes
     * @return $this
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * @return array
     */
    public function getPreConditions()
    {
        return $this->preConditions;
    }

    /**
     * @param array $preConditions
     * @return $this
     */
    public function setPreConditions(array $preConditions)
    {
        $this->preConditions = $preConditions;

        return $this;
    }

    /**
     * @return array
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    /**
     * @param array $conditions
     * @return $this
     */
    public function setConditions(array $conditions)
    {
        $this->conditions = $conditions;

        return $this;
    }

    /**
     * @return array
     */
    public function getInitStep()
    {
        return $this->initStep;
    }

    /**
     * @param array $initStep
     * @return $this
     */
    public function setInitStep(array $initStep)
    {
        $this->initStep = $initStep;

        return $this;
    }

    /**
     * @return array
     */
    public function getExecutionStep()
    {
        return $this->executionStep;
    }

    /**
     * @param array $executionStep
     * @return $this
     */
    public function setExecutionStep(array $executionStep)
    {
        $this->executionStep = $executionStep;

        return $this;
    }
}
