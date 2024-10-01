<?php

namespace Oro\Component\Expression\QueryExpressionConverter;

use Doctrine\ORM\Query\Expr;
use Oro\Component\Expression\Node\NodeInterface;
use Oro\Component\Expression\Node\RelationNode;

/**
 * Convert RelationNode to expression suitable for DQL.
 */
class RelationNodeConverter implements QueryExpressionConverterInterface
{
    #[\Override]
    public function convert(NodeInterface $node, Expr $expr, array &$params, array $aliasMapping = [])
    {
        $virtualColumnExpressions = $aliasMapping[QueryExpressionConverterInterface::MAPPING_COLUMNS] ?? [];
        $tableAliasMapping = $aliasMapping[QueryExpressionConverterInterface::MAPPING_TABLES] ?? [];
        if ($node instanceof RelationNode) {
            $aliasKey = $node->getResolvedContainer();
            if ($node->getField() && !empty($virtualColumnExpressions[$aliasKey][$node->getRelationField()])) {
                return $virtualColumnExpressions[$aliasKey][$node->getRelationField()];
            }
            if (array_key_exists($aliasKey, $tableAliasMapping)) {
                $container = $tableAliasMapping[$aliasKey];
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
