<?php

namespace Oro\Component\Expression\QueryExpressionConverter;

use Doctrine\ORM\Query\Expr;
use Oro\Component\Expression\Node\BinaryNode;
use Oro\Component\Expression\Node\NodeInterface;
use Oro\Component\Expression\Node\ValueNode;

class BinaryNodeConverter implements QueryExpressionConverterInterface, ConverterAwareInterface
{
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
            $left = $this->converter->convert($node->getLeft(), $expr, $params, $aliasMapping);
            $right = $this->converter->convert($node->getRight(), $expr, $params, $aliasMapping);

            if ($node->getOperation() === '%') {
                return new Expr\Func('MOD', [$left, $right]);
            }

            if (!array_key_exists($node->getOperation(), self::$exprMap)) {
                throw new \InvalidArgumentException(sprintf('Unsupported operation "%s"', $node->getOperation()));
            }

            $method = self::$exprMap[$node->getOperation()];
            if ($method === 'in' && !$node->getRight() instanceof ValueNode) {
                $method = 'isMemberOf';
            }
            if ($method === 'notIn' && !$node->getRight() instanceof ValueNode) {
                return $expr->not($expr->isMemberOf($left, $right));
            }

            return $expr->$method($left, $right);
        }

        return null;
    }
}
