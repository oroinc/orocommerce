<?php

namespace Oro\Component\Expression\QueryExpressionConverter;

use Doctrine\ORM\Query\Expr;
use Oro\Component\Expression\Node\NodeInterface;
use Oro\Component\Expression\Node\UnaryNode;

class UnaryNodeConverter implements QueryExpressionConverterInterface, ConverterAwareInterface
{
    /**
     * @var QueryExpressionConverterInterface
     */
    protected $converter;

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
        if ($node instanceof UnaryNode) {
            $convertedNode = $this->converter->convert($node->getNode(), $expr, $params, $aliasMapping);

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

        return null;
    }
}
