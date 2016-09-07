<?php

namespace Oro\Bundle\PricingBundle\Expression;

class NameNode implements NodeInterface, ContainerHolderNodeInterface
{
    /**
     * @var string
     */
    protected $container;

    /**
     * @var string|null
     */
    protected $field;

    /**
     * @var int|null|string
     */
    protected $containerId;

    /**
     * @param string $container
     * @param string|null $field
     * @param null|int|string $containerId
     */
    public function __construct($container, $field = null, $containerId = null)
    {
        $this->container = $container;
        $this->field = $field;
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
     * {@inheritdoc}
     */
    public function getNodes()
    {
        return [$this];
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

    /**
     * {@inheritdoc}
     */
    public function getResolvedContainer()
    {
        $alias = $this->getContainer();

        if ($this->getContainerId()) {
            $alias .= '|' . $this->getContainerId();
        }

        return $alias;
    }
}
