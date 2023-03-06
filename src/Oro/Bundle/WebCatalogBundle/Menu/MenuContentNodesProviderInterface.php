<?php

namespace Oro\Bundle\WebCatalogBundle\Menu;

use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

/**
 * Provides {@see ResolvedContentNode} for the specified {@see ContentNode} for using in menu.
 */
interface MenuContentNodesProviderInterface
{
    /**
     * @param ContentNode $contentNode
     * @param array $context Arbitrary context options to take into account.
     *                       Look into specific provider for available options.
     *
     * @return ResolvedContentNode|null
     */
    public function getResolvedContentNode(ContentNode $contentNode, array $context = []): ?ResolvedContentNode;
}
