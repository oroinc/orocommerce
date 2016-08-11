<?php

namespace OroB2B\Bundle\PricingBundle\Expression;

class NameNode implements NodeInterface
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
     * @param string $container
     * @param string|null $field
     */
    public function __construct($container, $field = null)
    {
        $this->container = $container;
        $this->field = $field;
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
}
