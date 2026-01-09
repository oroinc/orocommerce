<?php

namespace Oro\Component\Expression\Node;

/**
 * Represents a literal value node in an expression tree.
 *
 * A value node holds a constant value (integer, float, or string) that appears directly in an expression.
 * These nodes are leaf nodes in the expression tree and do not contain any subnodes.
 */
class ValueNode implements NodeInterface
{
    /**
     * @var int|float|string
     */
    protected $value;

    /**
     * @param int|float|string $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @return float|int|string
     */
    public function getValue()
    {
        return $this->value;
    }

    #[\Override]
    public function getNodes()
    {
        return [$this];
    }

    #[\Override]
    public function isBoolean()
    {
        return false;
    }
}
