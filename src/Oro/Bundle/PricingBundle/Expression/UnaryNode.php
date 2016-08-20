<?php

namespace Oro\Bundle\PricingBundle\Expression;

class UnaryNode implements NodeInterface
{
    /**
     * @var NodeInterface
     */
    protected $node;

    /**
     * @var string
     */
    protected $operation;

    /**
     * @param NodeInterface $node
     * @param string $operation
     */
    public function __construct(NodeInterface $node, $operation)
    {
        $this->node = $node;
        $this->operation = $operation;
    }

    /**
     * @return NodeInterface
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * @return string
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * {@inheritdoc}
     */
    public function getNodes()
    {
        return array_merge([$this], $this->node->getNodes());
    }

    /**
     * {@inheritdoc}
     */
    public function isBoolean()
    {
        return $this->getOperation() === 'not' && $this->getNode()->isBoolean();
    }
}
