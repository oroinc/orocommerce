<?php

namespace Oro\Bundle\PricingBundle\Expression;

class RelationNode implements NodeInterface
{
    /**
     * @var string
     */
    protected $container;

    /**
     * @var string
     */
    protected $field;

    /**
     * @var string
     */
    protected $relationField;

    /**
     * @var int|null|string
     */
    protected $containerId;

    /**
     * @param string $container
     * @param string $field
     * @param string $relationField
     * @param null|int|string $containerId
     */
    public function __construct($container, $field, $relationField, $containerId = null)
    {
        $this->container = $container;
        $this->field = $field;
        $this->relationField = $relationField;
        $this->containerId = $containerId;
    }

    /**
     * @return string
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @return null|string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @return string
     */
    public function getRelationField()
    {
        return $this->relationField;
    }

    /**
     * {@inheritdoc}
     */
    public function getNodes()
    {
        return [$this];
    }

    /**
     * @return string
     */
    public function getRelationAlias()
    {
        return $this->getContainer() . '::' . $this->getField();
    }

    /**
     * {@inheritdoc}
     */
    public function isBoolean()
    {
        return false;
    }

    /**
     * @return int|null|string
     */
    public function getContainerId()
    {
        return $this->containerId;
    }
}
