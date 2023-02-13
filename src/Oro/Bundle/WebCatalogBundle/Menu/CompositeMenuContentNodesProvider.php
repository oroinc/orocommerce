<?php

namespace Oro\Bundle\WebCatalogBundle\Menu;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

/**
 * Provides {@see ResolvedContentNode} for the specified {@see ContentNode} by delegating the call to inner providers.
 */
class CompositeMenuContentNodesProvider implements MenuContentNodesProviderInterface
{
    private MenuContentNodesProviderInterface $menuContentNodesProvider;

    private MenuContentNodesProviderInterface $menuContentNodesFrontendProvider;

    private FrontendHelper $frontendHelper;

    public function __construct(
        MenuContentNodesProviderInterface $menuContentNodesProvider,
        MenuContentNodesProviderInterface $menuContentNodesFrontendProvider,
        FrontendHelper $frontendHelper
    ) {
        $this->menuContentNodesProvider = $menuContentNodesProvider;
        $this->menuContentNodesFrontendProvider = $menuContentNodesFrontendProvider;
        $this->frontendHelper = $frontendHelper;
    }

    /**
     * @param ContentNode $contentNode
     * @param array $context
     *  [
     *      'tree_depth' => int, // Max depth to expand content node children. -1 stands for unlimited.
     *  ]
     *
     * @return ResolvedContentNode|null
     */
    public function getResolvedContentNode(ContentNode $contentNode, array $context = []): ?ResolvedContentNode
    {
        if ($this->frontendHelper->isFrontendRequest()) {
            return $this->menuContentNodesFrontendProvider->getResolvedContentNode($contentNode, $context);
        }

        return $this->menuContentNodesProvider->getResolvedContentNode($contentNode, $context);
    }
}
