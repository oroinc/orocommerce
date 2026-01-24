<?php

namespace Oro\Component\Expression;

use Oro\Component\Expression\Node\NodeInterface;

/**
 * Defines the contract for providers that populate column information for expression nodes.
 *
 * Implementations analyze expression nodes and populate column definitions and tracking information,
 * enabling the system to understand which database columns are referenced in expressions.
 */
interface ColumnInformationProviderInterface
{
    /**
     * @param NodeInterface $node
     * @param array $addedColumns
     * @param array $definition
     * @return bool
     */
    public function fillColumnInformation(NodeInterface $node, array &$addedColumns, array &$definition);
}
