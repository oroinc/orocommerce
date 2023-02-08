<?php

namespace Oro\Bundle\WebCatalogBundle\Cache;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;

/**
 * Merges resolved content nodes fetched for different scopes.
 */
class ResolvedContentNodesMerger
{
    /**
     * @param ResolvedContentNode[] $resolvedContentNodes
     *
     * @return array<int,ResolvedContentNode>
     */
    public function mergeResolvedNodes(array $resolvedContentNodes): array
    {
        $aggregatedById = [];
        foreach ($resolvedContentNodes as $resolvedNode) {
            $aggregatedById[$resolvedNode->getId()][] = $resolvedNode;
        }

        /** @var array<int,ResolvedContentNode> $result */
        $result = [];
        foreach ($aggregatedById as $nodeId => $aggregatedNodes) {
            $childNodes = [];
            foreach ($aggregatedNodes as $resolvedNode) {
                $childNodes[] = $resolvedNode->getChildNodes()->toArray();
            }

            $childNodes = array_merge(...$childNodes);
            $childNodes = $this->mergeResolvedNodes($childNodes);

            $result[$nodeId] = reset($aggregatedNodes);
            $result[$nodeId]->setChildNodes(new ArrayCollection(array_values($childNodes)));
        }

        uasort($result, static fn ($node1, $node2) => $node1->getPriority() <=> $node2->getPriority());

        return $result;
    }
}
