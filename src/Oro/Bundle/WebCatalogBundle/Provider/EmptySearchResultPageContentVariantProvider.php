<?php

namespace Oro\Bundle\WebCatalogBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Utils\TreeUtils;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\DependencyInjection\Configuration;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

/**
 * Provides {@see ResolvedContentVariant} by {@see ContentNode} from an "Empty Search Result Page" configuration option.
 */
class EmptySearchResultPageContentVariantProvider
{
    public function __construct(
        private ConfigManager $configManager,
        private ContentNodeTreeResolverInterface $contentNodeTreeResolver,
        private RequestWebContentScopeProvider $requestWebContentScopeProvider
    ) {
    }

    public function getResolvedContentVariant(): ?ResolvedContentVariant
    {
        $emptySearchResultPageKey = TreeUtils::getConfigKey(
            Configuration::ROOT_NODE,
            Configuration::EMPTY_SEARCH_RESULT_PAGE
        );
        $emptySearchResultPage = $this->configManager->get($emptySearchResultPageKey) ?? [];
        $contentNode = $emptySearchResultPage['contentNode'] ?? null;
        $contentNodeId = $contentNode?->getId() ?? null;
        if ($contentNodeId === null) {
            return null;
        }

        $scopes = $this->requestWebContentScopeProvider->getScopes();
        $resolvedContentNode = $this->contentNodeTreeResolver
            ->getResolvedContentNode($contentNode, $scopes, ['tree_depth' => 0]);

        return $resolvedContentNode?->getResolvedContentVariant();
    }
}
