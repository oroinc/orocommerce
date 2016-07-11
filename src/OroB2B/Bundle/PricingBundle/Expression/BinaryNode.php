<?php

namespace OroB2B\Bundle\PricingBundle\Expression;

class BinaryNode implements NodeInterface
{
    /**
     * @var array
     */
    protected static $booleanOperations = [
        'and' => true,
        'or' => true,
        '==' => true,
        '!=' => true,
        '>' => true,
        '<' => true,
        '<=' => true,
        '>=' => true
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * @return bool
     */
    public function isBooleanOperation()
    {
        return !$this->isMathOperation();
    }

    /**
     * @return bool
     */
    public function isMathOperation()
    {
        return empty(self::$booleanOperations[$this->getOperation()]);
    }
}
