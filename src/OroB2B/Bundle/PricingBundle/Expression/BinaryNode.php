<?php

namespace OroB2B\Bundle\PricingBundle\Expression;

class BinaryNode implements NodeInterface, OperationAwareInterface
{
    /**
     * @var NodeInterface
     */
    protected $left;

    /**
     * @var NodeInterface
     */
    protected $right;

    /**
     * @var string
     */
    protected $operation;
    
    public function __construct(NodeInterface $left, NodeInterface $right, $operation)
    {
        $this->left = $left;
        $this->right = $right;
        $this->operation = $operation;
    }

    /**
     * @return NodeInterface
     */
    public function getLeft()
    {
        return $this->left;
    }

    /**
     * @return NodeInterface
     */
    public function getRight()
    {
        return $this->right;
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
        return array_merge([$this], $this->left->getNodes(), $this->right->getNodes());
    }
}
