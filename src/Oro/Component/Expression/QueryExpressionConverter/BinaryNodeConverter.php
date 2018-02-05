<?php

namespace Oro\Component\Expression\QueryExpressionConverter;

use Doctrine\ORM\Query\Expr;
use Oro\Component\Expression\Node\BinaryNode;
use Oro\Component\Expression\Node\NodeInterface;
use Oro\Component\Expression\Node\ValueNode;

class BinaryNodeConverter implements QueryExpressionConverterInterface, ConverterAwareInterface
{
    const TYPE_LOGICAL = 1;
    const TYPE_MATH = 2;
    const TYPE_COMPARISON = 3;

    /**
     * @var QueryExpressionConverterInterface
     */
    protected $converter;

    /**
     * @var array
     */
    protected static $exprMap = [
        'and' => 'andX',
        'or' => 'orX',
        'like' => 'like',
        'in' => 'in',
        'not in' => 'notIn',
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
     * @var array
     */
    protected static $exprTypeMap = [
        'andX' => self::TYPE_LOGICAL,
        'orX' => self::TYPE_LOGICAL,

        'sum' => self::TYPE_MATH,
        'diff' => self::TYPE_MATH,
        'prod' => self::TYPE_MATH,
        'quot' => self::TYPE_MATH,

        'like' => self::TYPE_COMPARISON,
        'in' => self::TYPE_COMPARISON,
        'notIn' => self::TYPE_COMPARISON,
        'gt' => self::TYPE_COMPARISON,
        'gte' => self::TYPE_COMPARISON,
        'lt' => self::TYPE_COMPARISON,
        'lte' => self::TYPE_COMPARISON,
        'eq' => self::TYPE_COMPARISON,
        'neq' => self::TYPE_COMPARISON
    ];

    /**
     * {@inheritdoc}
     */
    public function setConverter(QueryExpressionConverterInterface $converter)
    {
        $this->converter = $converter;
    }

    /**
     * {@inheritdoc}
     */
    public function convert(NodeInterface $node, Expr $expr, array &$params, array $aliasMapping = [])
    {
        if ($node instanceof BinaryNode) {
            $method = null;
            if ($node->getOperation() !== '%') {
                if (!array_key_exists($node->getOperation(), self::$exprMap)) {
                    throw new \InvalidArgumentException(sprintf('Unsupported operation "%s"', $node->getOperation()));
                }
                $method = self::$exprMap[$node->getOperation()];
            }

            $left = $this->converter->convert($node->getLeft(), $expr, $params, $aliasMapping);
            // Always use parametrized values for comparison with value nodes
            if ($method
                && self::$exprTypeMap[$method] === self::TYPE_COMPARISON
                && $node->getRight() instanceof ValueNode
            ) {
                $params[self::REQUIRE_PARAMETRIZATION] = true;
            }
            $right = $this->converter->convert($node->getRight(), $expr, $params, $aliasMapping);
            unset($params[self::REQUIRE_PARAMETRIZATION]);

            return $this->getExpression($node, $expr, $right, $left, $method);
        }

        return null;
    }

    /**
     * @param BinaryNode $node
     * @param Expr $expr
     * @param Expr\Base|string|null $right
     * @param Expr\Base|string|null $left
     * @param string $method
     * @return Expr\Func
     */
    private function getExpression(BinaryNode $node, Expr $expr, $right, $left, $method)
    {
        if ($node->getOperation() === '%') {
            return new Expr\Func('MOD', [$left, $right]);
        }

        if ($method === 'in' && !$node->getRight() instanceof ValueNode) {
            $method = 'isMemberOf';
        }

        if ($method === 'notIn' && !$node->getRight() instanceof ValueNode) {
            return $expr->not($expr->isMemberOf($left, $right));
        }

        return $expr->$method($left, $right);
    }
}
