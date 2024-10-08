<?php

namespace Oro\Bundle\WebCatalogBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Utils\TreeUtils;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\DependencyInjection\Configuration;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Provides {@see ResolvedContentVariant} by {@see ContentNode} from an "Empty Search Result Page" configuration option.
 */
class EmptySearchResultPageContentVariantProvider implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private ConfigManager $configManager,
        private ManagerRegistry $doctrine,
        private ContentNodeTreeResolverInterface $contentNodeTreeResolver,
        private RequestWebContentScopeProvider $requestWebContentScopeProvider
    ) {
        $this->logger = new NullLogger();
    }

    public function getResolvedContentVariant(): ?ResolvedContentVariant
    {
        $emptySearchResultPageKey = TreeUtils::getConfigKey(
            Configuration::ROOT_NODE,
            Configuration::EMPTY_SEARCH_RESULT_PAGE
        );
        $contentNodeId = $this->configManager->get($emptySearchResultPageKey);
        if ($contentNodeId === null) {
            return null;
        }

        $contentNode = $this->doctrine?->getRepository(ContentNode::class)->find($contentNodeId);
        if ($contentNode === null) {
            $this->logger->error(
                'Content node #{id} (fetched from "{system_config}" system config) '
                . 'for the empty search result page is not found',
                [
                    'id' => $contentNodeId,
                    'system_config' => $emptySearchResultPageKey,
                ]
            );

            return null;
        }

        $scopes = $this->requestWebContentScopeProvider->getScopes();
        $resolvedContentNode = $this->contentNodeTreeResolver
            ->getResolvedContentNode($contentNode, $scopes, ['tree_depth' => 0]);

        return $resolvedContentNode?->getResolvedContentVariant();
    }
}
