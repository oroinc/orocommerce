<?php

namespace Oro\Component\Expression\Node;

/**
 * Represents a binary operation node in an expression tree.
 *
 * A binary node combines two operands (left and right) with an operation (e.g., +, -, and, or, ==, !=, etc.).
 * It supports boolean operations (and, or), comparison expressions (==, !=, >, <, <=, >=, like, in, not in),
 * and mathematical operations (+, -, *, /). The class normalizes operation symbols to a canonical form
 * and provides methods to determine whether the operation is boolean, mathematical, or a comparison.
 */
class BinaryNode implements NodeInterface
{
    /**
     * @var array
     */
    protected static $booleanOperations = [
        'and' => true,
        'or' => true,
    ];

    /**
     * @var array
     */
    protected static $booleanExpressions = [
        '==' => true,
        '!=' => true,
        '>' => true,
        '<' => true,
        '<=' => true,
        '>=' => true,
        'like' => true,
        'in' => true,
        'not in' => true
    ];

    /**
     * @var array
     */
    protected static $operationMapping = [
        '===' => '==',
        '!==' => '!=',
        '<>' => '!=',
        '=<' => '<=',
        '=>' => '>=',
        '&&' => 'and',
        '||' => 'or',
        'matches' => 'like'
    ];

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

    /**
     * @param NodeInterface $left
     * @param NodeInterface $right
     * @param string $operation
     */
    public function __construct(NodeInterface $left, NodeInterface $right, $operation)
    {
        $this->left = $left;
        $this->right = $right;
        $this->setOperation($operation);
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

    #[\Override]
    public function getNodes()
    {
        return array_merge([$this], $this->left->getNodes(), $this->right->getNodes());
    }

    /**
     * @param string $operation
     */
    protected function setOperation($operation)
    {
        if (array_key_exists($operation, self::$operationMapping)) {
            $operation = self::$operationMapping[$operation];
        }

        $this->operation = $operation;
    }

    /**
     * @return string
     */
    public function getOperation()
    {
        return $this->operation;
    }

    #[\Override]
    public function isBoolean()
    {
        if ($this->isBooleanOperation() || $this->isBooleanExpression()) {
            return true;
        }

        return $this->getLeft()->isBoolean() || $this->getRight()->isBoolean();
    }

    /**
     * @return bool
     */
    protected function isBooleanOperation()
    {
        return !empty(self::$booleanOperations[$this->getOperation()]);
    }

    /**
     * @return bool
     */
    protected function isBooleanExpression()
    {
        return !empty(self::$booleanExpressions[$this->getOperation()]);
    }

    /**
     * @return bool
     */
    public function isMathOperation()
    {
        $operation = $this->getOperation();

        return empty(self::$booleanOperations[$operation]) && empty(self::$booleanExpressions[$operation]);
    }
}
