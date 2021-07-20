<?php

namespace Oro\Bundle\WebCatalogBundle\Cache;

use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

/**
 * Restores content nodes tree from cache
 */
class ContentNodeTreeResolver implements ContentNodeTreeResolverInterface
{
    /** @var ContentNodeTreeResolverInterface */
    private $innerResolver;

    /** @var ContentNodeTreeCache */
    private $cache;

    public function __construct(
        ContentNodeTreeResolverInterface $innerResolver,
        ContentNodeTreeCache $cache
    ) {
        $this->innerResolver = $innerResolver;
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function getResolvedContentNode(ContentNode $node, Scope $scope): ?ResolvedContentNode
    {
        $resolvedNode = $this->cache->fetch($node->getId(), $scope->getId());
        if (false === $resolvedNode) {
            $resolvedNode = $this->innerResolver->getResolvedContentNode($node, $scope);
            $this->cache->save($node->getId(), $scope->getId(), $resolvedNode);
        }

        return $resolvedNode;
    }
}
