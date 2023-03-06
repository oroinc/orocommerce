<?php

namespace Oro\Bundle\WebCatalogBundle\Cache;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

/**
 * Resolves a content node from the cached root content node.
 */
class ContentNodeTreeCachingResolver implements ContentNodeTreeResolverInterface
{
    private ContentNodeTreeResolverInterface $innerResolver;

    private ManagerRegistry $managerRegistry;

    /**
     * @var ContentNodeTreeCache Contains resolved content nodes for separate scope ids,
     * always starting from the root node.
     */
    private ContentNodeTreeCache $rootContentNodeTreeCache;

    public function __construct(
        ContentNodeTreeResolverInterface $innerResolver,
        ManagerRegistry $managerRegistry,
        ContentNodeTreeCache $rootContentNodeTreeCache
    ) {
        $this->innerResolver = $innerResolver;
        $this->managerRegistry = $managerRegistry;
        $this->rootContentNodeTreeCache = $rootContentNodeTreeCache;
    }

    /**
     * @param ContentNode $node
     * @param Scope|array $scopes
     * @param array $context Available context options:
     *  [
     *      'tree_depth' => int, // Restricts the maximum tree depth. -1 stands for unlimited.
     *  ]
     * @return ResolvedContentNode|null
     */
    public function getResolvedContentNode(
        ContentNode $node,
        Scope|array $scopes,
        array $context = []
    ): ?ResolvedContentNode {
        $scopes = !is_array($scopes) ? [$scopes] : $scopes;
        $scopeIds = $this->getScopeIds($scopes);
        $treeDepth = (int)($context['tree_depth'] ?? -1);
        $rootNodeId = $node->getRoot();

        $resolvedRootNode = $this->rootContentNodeTreeCache->fetch($rootNodeId, $scopeIds, -1);
        if ($resolvedRootNode === false) {
            $rootNode = $this->managerRegistry
                ->getManagerForClass(ContentNode::class)
                ?->find(ContentNode::class, $rootNodeId);
            if (!$rootNode) {
                return null;
            }

            // Intentionally passes the tree_depth -1 to get the whole tree and cache it.
            $resolvedRootNode = $this->innerResolver->getResolvedContentNode($rootNode, $scopes, ['tree_depth' => -1]);
            $this->rootContentNodeTreeCache->save($rootNodeId, $scopeIds, $resolvedRootNode);
        }

        return $resolvedRootNode
            ? $this->findInResolvedNode($resolvedRootNode, $node->getId(), $treeDepth)
            : null;
    }

    private function getScopeIds(array $scopes): array
    {
        $scopeIds = [];
        foreach ($scopes as $scope) {
            $scopeIds[] = $scope->getId();
        }

        return $scopeIds;
    }

    private function findInResolvedNode(
        ResolvedContentNode $resolvedNode,
        int $nodeId,
        int $treeDepth
    ): ?ResolvedContentNode {
        if ($nodeId === $resolvedNode->getId()) {
            return $resolvedNode;
        }

        foreach ($resolvedNode->getChildNodes() as $resolvedChildNode) {
            $foundResolvedNode = $this->findInResolvedNode($resolvedChildNode, $nodeId, $treeDepth);
            if ($foundResolvedNode !== null) {
                $this->applyTreeDepth($foundResolvedNode, $treeDepth);

                return $foundResolvedNode;
            }
        }

        return null;
    }

    private function applyTreeDepth(ResolvedContentNode $resolvedNode, int $treeDepth): void
    {
        if ($treeDepth === 0) {
            $resolvedNode->setChildNodes(new ArrayCollection());
        }

        $treeDepth--;
        foreach ($resolvedNode->getChildNodes() as $resolvedChildNode) {
            $this->applyTreeDepth($resolvedChildNode, $treeDepth);
        }
    }
}
