<?php

namespace Oro\Component\Expression\Node;

/**
 * Defines the contract for expression language nodes.
 *
 * Nodes represent the abstract syntax tree (AST) of parsed expressions and can be composed
 * hierarchically to represent complex expressions. Each node can determine whether it represents
 * a boolean expression and can provide access to all its subnodes.
 */
interface NodeInterface
{
    /**
     * Get current node and all it's subnodes.
     *
     * @return array
     */
    public function getNodes();

    /**
     * @return bool
     */
    public function isBoolean();
}
