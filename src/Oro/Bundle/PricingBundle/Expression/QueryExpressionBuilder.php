<?php

namespace Oro\Bundle\PricingBundle\Expression;

use Doctrine\ORM\Query\Expr;

class QueryExpressionBuilder
{
    const PARAMETER_PREFIX = '_vn';

    /**
     * @var int
     */
    protected $paramCount = 0;

    /**
     * @var array
     */
    protected static $exprMap = [
        'and' => 'andX',
        'or' => 'orX',
        'like' => 'like',
        '+' => 'sum',
        '-' => 'diff',
        '*' => 'prod',
        '/' => 'quot',
        '>' => 'gt',
        '>=' => 'gte',
        '<' => 'lt',
        '<=' => 'lte',
        '==' => 'eq',
        '!=' => 'neq'
    ];

    /**
     * @param NodeInterface $node
     * @param Expr $expr
     * @param array $params
     * @param array $aliasMapping
     * @return Expr\Base|string
     */
    public function convert(NodeInterface $node, Expr $expr, array &$params, array $aliasMapping = [])
    {
        if ($node instanceof NameNode) {
            return $this->convertNameNode($node, $aliasMapping);
        }
        if ($node instanceof RelationNode) {
            return $this->convertRelationNode($node, $aliasMapping);
        }

        if ($node instanceof UnaryNode) {
            return $this->convertUnaryNode($node, $expr, $params, $aliasMapping);
        }
        if ($node instanceof BinaryNode) {
            return $this->convertBinaryNode($node, $expr, $params, $aliasMapping);
        }

        if ($node instanceof ValueNode) {
            return $this->convertValueNode($node, $params);
        }

        throw new \InvalidArgumentException(sprintf('Unsupported node type %s', get_class($node)));
    }

    /**
     * @param UnaryNode $node
     * @param Expr $expr
     * @param array $params
     * @param array $aliasMapping
     * @return Expr\Func|string
     */
    protected function convertUnaryNode(UnaryNode $node, Expr $expr, array &$params, array $aliasMapping)
    {
        $convertedNode = $this->convert($node->getNode(), $expr, $params, $aliasMapping);

        switch ($node->getOperation()) {
            case 'not':
                return $expr->not($convertedNode);
            case '-':
                return '(-' . (string)$convertedNode . ')';
            case '+':
            default:
                return $convertedNode;
        }
    }

    /**
     * @param BinaryNode $node
     * @param Expr $expr
     * @param array $params
     * @param array $aliasMapping
     * @return Expr\Andx|Expr\Orx|string
     */
    protected function convertBinaryNode(BinaryNode $node, Expr $expr, array &$params, array $aliasMapping)
    {
        if (!array_key_exists($node->getOperation(), self::$exprMap)) {
            throw new \InvalidArgumentException(sprintf('Unsupported operation "%s"', $node->getOperation()));
        }

        $method = self::$exprMap[$node->getOperation()];

        return $expr->$method(
            $this->convert($node->getLeft(), $expr, $params, $aliasMapping),
            $this->convert($node->getRight(), $expr, $params, $aliasMapping)
        );
    }

    /**
     * @param NameNode $node
     * @param array $aliasMapping
     * @return string
     */
    protected function convertNameNode(NameNode $node, array $aliasMapping)
    {
        $container = $node->getContainer();
        $aliasKey = $node->getNodeAlias();
        if (array_key_exists($aliasKey, $aliasMapping)) {
            $container = $aliasMapping[$aliasKey];
        }

        return $node->getField() ? $container . '.' . $node->getField() : $container;
    }

    /**
     * @param RelationNode $node
     * @param array $aliasMapping
     * @return string
     */
    protected function convertRelationNode(RelationNode $node, array $aliasMapping)
    {
        $container = $node->getContainer() . '.' . $node->getField();
        $aliasKey = $node->getNodeAlias();
        if (array_key_exists($aliasKey, $aliasMapping)) {
            $container = $aliasMapping[$aliasKey];
        }

        return $container . '.' . $node->getRelationField();
    }

    /**
     * @param ValueNode $node
     * @param array $params
     * @return string
     */
    protected function convertValueNode(ValueNode $node, array &$params)
    {
        $value = $node->getValue();
        if (!is_numeric($value)) {
            $param = self::PARAMETER_PREFIX . $this->paramCount;
            $params[$param] = $value;
            $value = ':' . $param;
            $this->paramCount++;
        }

        return $value;
    }
}
