<?php

namespace Oro\Bundle\WebCatalogBundle\Cache;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\WebCatalogRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;

/**
 * Creates a dump of web catalog content node tree and saves it to the cache.
 */
class ContentNodeTreeCacheDumper
{
    private ManagerRegistry $managerRegistry;

    private ContentNodeTreeResolverInterface $contentNodeTreeResolver;

    private ContentNodeTreeCache $contentNodeTreeCache;

    private ContentNodeTreeCache $mergedContentNodeTreeCache;

    public function __construct(
        ManagerRegistry $managerRegistry,
        ContentNodeTreeResolverInterface $contentNodeTreeResolver,
        ContentNodeTreeCache $contentNodeTreeCache,
        ContentNodeTreeCache $mergedContentNodeTreeCache
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->contentNodeTreeResolver = $contentNodeTreeResolver;
        $this->contentNodeTreeCache = $contentNodeTreeCache;
        $this->mergedContentNodeTreeCache = $mergedContentNodeTreeCache;
    }

    public function dump(ContentNode $node, Scope $scope): void
    {
        $this->mergedContentNodeTreeCache->clear();

        $this->doDump($node, $scope);
    }

    public function dumpForAllScopes(WebCatalog $webCatalog): void
    {
        $this->mergedContentNodeTreeCache->clear();

        $rootNode = $this->getContentNodeRepository()->getRootNodeByWebCatalog($webCatalog);
        if ($rootNode) {
            $scopes = $this->getWebCatalogRepository()->getUsedScopes($webCatalog);
            foreach ($scopes as $scope) {
                $this->doDump($rootNode, $scope);
            }
        }
    }

    private function doDump(ContentNode $node, Scope $scope): void
    {
        // delete existing cached data
        $this->contentNodeTreeCache->delete($node->getId(), [$scope->getId()]);
        // build web catalog node tree and save it to the cache
        $this->contentNodeTreeResolver->getResolvedContentNode($node, $scope);
    }

    private function getContentNodeRepository(): ContentNodeRepository
    {
        return $this->managerRegistry->getRepository(ContentNode::class);
    }

    private function getWebCatalogRepository(): WebCatalogRepository
    {
        return $this->managerRegistry->getRepository(WebCatalog::class);
    }
}
