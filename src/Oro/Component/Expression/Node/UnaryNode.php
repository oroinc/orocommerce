<?php

namespace Oro\Component\Expression\Node;

/**
 * Represents a unary operation node in an expression tree.
 *
 * A unary node applies a single operation (e.g., not, -, +) to a single operand.
 * The 'not' operation is the primary boolean unary operation, while '-' and '+' are used
 * for arithmetic negation and positive sign operations.
 */
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

    #[\Override]
    public function getNodes()
    {
        return array_merge([$this], $this->node->getNodes());
    }

    #[\Override]
    public function isBoolean()
    {
        return $this->getOperation() === 'not' && $this->getNode()->isBoolean();
    }
}
