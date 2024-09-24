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

    #[\Override]
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

    #[\Override]
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

    #[\Override]
    public function getResolvedContainer()
    {
        $alias = $this->getRelationAlias();

        if ($this->getContainerId()) {
            $alias .= '|' . $this->getContainerId();
        }

        return $alias;
    }

    #[\Override]
    public function isBoolean()
    {
        return false;
    }

    #[\Override]
    public function getContainerId()
    {
        return $this->containerId;
    }
}
