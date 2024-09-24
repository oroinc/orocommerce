<?php

namespace Oro\Component\Expression\QueryExpressionConverter;

use Doctrine\ORM\Query\Expr;
use Oro\Component\Expression\Node\NameNode;
use Oro\Component\Expression\Node\NodeInterface;

/**
 * Convert NameNode to expression suitable for DQL.
 */
class NameNodeConverter implements QueryExpressionConverterInterface
{
    #[\Override]
    public function convert(NodeInterface $node, Expr $expr, array &$params, array $aliasMapping = [])
    {
        $virtualColumnExpressions = $aliasMapping[QueryExpressionConverterInterface::MAPPING_COLUMNS] ?? [];
        $tableAliasMapping = $aliasMapping[QueryExpressionConverterInterface::MAPPING_TABLES] ?? [];
        if ($node instanceof NameNode) {
            $aliasKey = $node->getResolvedContainer();
            if ($node->getField() && !empty($virtualColumnExpressions[$aliasKey][$node->getField()])) {
                return $virtualColumnExpressions[$aliasKey][$node->getField()];
            }
            if (array_key_exists($aliasKey, $tableAliasMapping)) {
                $container = $tableAliasMapping[$aliasKey];
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
