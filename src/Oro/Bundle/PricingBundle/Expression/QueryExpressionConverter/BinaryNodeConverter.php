<?php

namespace Oro\Bundle\PricingBundle\Expression\QueryExpressionConverter;

use Doctrine\ORM\Query\Expr;
use Oro\Bundle\PricingBundle\Expression\BinaryNode;
use Oro\Bundle\PricingBundle\Expression\NodeInterface;
use Oro\Bundle\PricingBundle\Expression\ValueNode;

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
            if (!array_key_exists($node->getOperation(), self::$exprMap)) {
                throw new \InvalidArgumentException(sprintf('Unsupported operation "%s"', $node->getOperation()));
            }

            $method = self::$exprMap[$node->getOperation()];
            $left = $this->converter->convert($node->getLeft(), $expr, $params, $aliasMapping);
            $right = $this->converter->convert($node->getRight(), $expr, $params, $aliasMapping);

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
