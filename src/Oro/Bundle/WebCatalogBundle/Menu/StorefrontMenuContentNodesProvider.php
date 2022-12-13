<?php

namespace Oro\Bundle\WebCatalogBundle\Menu;

use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Provider\RequestWebContentScopeProvider;

/**
 * Provides {@see ResolvedContentNode} for the specified {@see ContentNode} taking into account the current scopes.
 */
class StorefrontMenuContentNodesProvider implements MenuContentNodesProviderInterface
{
    private RequestWebContentScopeProvider $requestWebContentScopeProvider;

    private ContentNodeTreeResolverInterface $contentNodeTreeResolver;

    public function __construct(
        RequestWebContentScopeProvider $requestWebContentScopeProvider,
        ContentNodeTreeResolverInterface $contentNodeTreeResolver
    ) {
        $this->requestWebContentScopeProvider = $requestWebContentScopeProvider;
        $this->contentNodeTreeResolver = $contentNodeTreeResolver;
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
        $scopes = (array)$this->requestWebContentScopeProvider->getScopes();
        if (!$scopes) {
            return null;
        }

        return $this->contentNodeTreeResolver
            ->getResolvedContentNode($contentNode, $scopes, ['tree_depth' => $context['tree_depth'] ?? -1]);
    }
}
