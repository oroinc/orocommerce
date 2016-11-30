<?php

namespace Oro\Component\Expression\Node;

class RelationNode implements NodeInterface, ContainerHolderNodeInterface
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
     * {@inheritdoc}
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
    public function getResolvedContainer()
    {
        $alias = $this->getRelationAlias();

        if ($this->getContainerId()) {
            $alias .= '|' . $this->getContainerId();
        }

        return $alias;
    }

    /**
     * {@inheritdoc}
     */
    public function isBoolean()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getContainerId()
    {
        return $this->containerId;
    }
}
