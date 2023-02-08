<?php

namespace Oro\Bundle\WebCatalogBundle\Cache;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

/**
 * Resolves content node for each scope separately and merges the results.
 */
class ContentNodeTreeMergingResolver implements ContentNodeTreeResolverInterface
{
    private ContentNodeTreeResolverInterface $innerResolver;

    private ResolvedContentNodesMerger $resolvedContentNodesMerger;

    /**
     * @var ContentNodeTreeCache Contains merged content nodes trees resolved for multiple scopes.
     */
    private ContentNodeTreeCache $mergedContentNodeTreeCache;

    public function __construct(
        ContentNodeTreeResolverInterface $innerResolver,
        ResolvedContentNodesMerger $resolvedContentNodesMerger,
        ContentNodeTreeCache $mergedContentNodeTreeCache
    ) {
        $this->innerResolver = $innerResolver;
        $this->resolvedContentNodesMerger = $resolvedContentNodesMerger;
        $this->mergedContentNodeTreeCache = $mergedContentNodeTreeCache;
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

        $nodeId = $node->getId();

        $resolvedNode = $this->mergedContentNodeTreeCache->fetch($nodeId, $scopeIds, $treeDepth);
        if ($resolvedNode === false) {
            /** @var array<ResolvedContentNode|null> $resolvedNodes */
            $resolvedNodes = [];
            foreach ($scopes as $scope) {
                // Intentionally passes the tree_depth -1 to get the whole tree, merge and cache it.
                $resolvedNodes[] = $this->innerResolver->getResolvedContentNode($node, [$scope], ['tree_depth' => -1]);
            }

            $resolvedNode = $this->mergeResolvedNodes($resolvedNodes, $nodeId);

            $this->mergedContentNodeTreeCache->save($nodeId, $scopeIds, $resolvedNode);

            if ($resolvedNode) {
                $this->applyTreeDepth($resolvedNode, $treeDepth);
            }
        }

        return $resolvedNode;
    }

    private function mergeResolvedNodes(array $resolvedNodes, int $nodeId): ?ResolvedContentNode
    {
        $resolvedNodes = array_filter($resolvedNodes);
        if (count($resolvedNodes) > 1) {
            $resolvedNodes = $this->resolvedContentNodesMerger->mergeResolvedNodes($resolvedNodes);
            $resolvedNode = $resolvedNodes[$nodeId] ?? null;
        } else {
            $resolvedNode = $resolvedNodes ? reset($resolvedNodes) : null;
        }

        return $resolvedNode;
    }

    private function getScopeIds(array $scopes): array
    {
        $scopeIds = [];
        foreach ($scopes as $scope) {
            $scopeIds[] = $scope->getId();
        }

        return $scopeIds;
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
