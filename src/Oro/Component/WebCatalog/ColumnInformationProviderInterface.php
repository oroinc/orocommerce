<?php

namespace Oro\Component\WebCatalog;

use Oro\Component\Expression\Node\NodeInterface;

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
