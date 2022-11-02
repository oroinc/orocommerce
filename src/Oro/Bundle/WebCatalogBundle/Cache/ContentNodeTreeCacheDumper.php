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
    /** @var ContentNodeTreeResolverInterface */
    private $contentNodeTreeResolver;

    /** @var ContentNodeTreeCache */
    private $contentNodeTreeCache;

    /** @var ManagerRegistry */
    private $doctrine;

    public function __construct(
        ContentNodeTreeResolverInterface $contentNodeTreeResolver,
        ContentNodeTreeCache $contentNodeTreeCache,
        ManagerRegistry $doctrine
    ) {
        $this->contentNodeTreeResolver = $contentNodeTreeResolver;
        $this->contentNodeTreeCache = $contentNodeTreeCache;
        $this->doctrine = $doctrine;
    }

    public function dump(ContentNode $node, Scope $scope): void
    {
        // delete existing cached data
        $this->contentNodeTreeCache->delete($node->getId(), $scope->getId());
        // build web catalog node tree and save it to the cache
        $this->contentNodeTreeResolver->getResolvedContentNode($node, $scope);
    }

    public function dumpForAllScopes(WebCatalog $webCatalog): void
    {
        $rootNode = $this->getContentNodeRepository()->getRootNodeByWebCatalog($webCatalog);
        if ($rootNode) {
            $scopes = $this->getWebCatalogRepository()->getUsedScopes($webCatalog);
            foreach ($scopes as $scope) {
                $this->dump($rootNode, $scope);
            }
        }
    }

    private function getContentNodeRepository(): ContentNodeRepository
    {
        return $this->doctrine->getRepository(ContentNode::class);
    }

    private function getWebCatalogRepository(): WebCatalogRepository
    {
        return $this->doctrine->getRepository(WebCatalog::class);
    }
}
