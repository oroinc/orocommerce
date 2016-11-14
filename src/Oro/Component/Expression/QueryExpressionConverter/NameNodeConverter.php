<?php

namespace Oro\Component\Expression\QueryExpressionConverter;

use Doctrine\ORM\Query\Expr;
use Oro\Component\Expression\Node\NameNode;
use Oro\Component\Expression\Node\NodeInterface;

class NameNodeConverter implements QueryExpressionConverterInterface
{
    /**
     * {@inheritdoc}
     */
    public function convert(NodeInterface $node, Expr $expr, array &$params, array $aliasMapping = [])
    {
        if ($node instanceof NameNode) {
            $aliasKey = $node->getResolvedContainer();
            if (array_key_exists($aliasKey, $aliasMapping)) {
                $container = $aliasMapping[$aliasKey];
            } else {
                throw new \InvalidArgumentException(
                    sprintf('No table alias found for "%s"', $aliasKey)
                );
            }

            return $node->getField() ? $container . '.' . $node->getField() : $container;
        }

        return null;
    }
}
