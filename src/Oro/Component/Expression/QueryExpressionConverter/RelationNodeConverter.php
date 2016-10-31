<?php

namespace Oro\Component\Expression\QueryExpressionConverter;

use Doctrine\ORM\Query\Expr;
use Oro\Component\Expression\Node\NodeInterface;
use Oro\Component\Expression\Node\RelationNode;

class RelationNodeConverter implements QueryExpressionConverterInterface
{
    /**
     * {@inheritdoc}
     */
    public function convert(NodeInterface $node, Expr $expr, array &$params, array $aliasMapping = [])
    {
        if ($node instanceof RelationNode) {
            $aliasKey = $node->getResolvedContainer();
            if (array_key_exists($aliasKey, $aliasMapping)) {
                $container = $aliasMapping[$aliasKey];
            } else {
                throw new \InvalidArgumentException(
                    sprintf('No table alias found for relation "%s"', $aliasKey)
                );
            }

            return $container . '.' . $node->getRelationField();
        }

        return null;
    }
}
