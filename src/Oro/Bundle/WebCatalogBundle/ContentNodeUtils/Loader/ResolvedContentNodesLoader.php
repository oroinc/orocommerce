<?php

namespace Oro\Bundle\WebCatalogBundle\ContentNodeUtils\Loader;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\Factory\ResolvedContentNodeFactory;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

/**
 * Creates {@see ResolvedContentNode} objects for specified {@see ContentNode} and {@see ContentVariant} IDs.
 */
class ResolvedContentNodesLoader
{
    private ManagerRegistry $managerRegistry;

    private ResolvedContentVariantsLoader $resolvedContentVariantsLoader;

    private ResolvedContentNodeFactory $resolvedContentNodeFactory;

    public function __construct(
        ManagerRegistry $managerRegistry,
        ResolvedContentVariantsLoader $resolvedContentVariantsLoader,
        ResolvedContentNodeFactory $resolvedContentNodeFactory
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->resolvedContentVariantsLoader = $resolvedContentVariantsLoader;
        $this->resolvedContentNodeFactory = $resolvedContentNodeFactory;
    }

    /**
     * @param int[]|array<int,int> $contentNodeIds Either a list of content node IDs or an associative array
     *  where key is a content node id and value is a content variant id.
     *
     * @return array<int,ResolvedContentNode>
     *  [
     *      int $contentNodeId => ResolvedContentNode,
     *      // ...
     *  ]
     */
    public function loadResolvedContentNodes(array $contentNodeIds): array
    {
        if (!$contentNodeIds) {
            return [];
        }

        $contentVariantIds = [];
        if (!array_is_list($contentNodeIds)) {
            $contentVariantIds = array_values($contentNodeIds);
            $contentNodeIds = array_keys($contentNodeIds);
        }

        $contentNodesData = $this->managerRegistry
            ->getRepository(ContentNode::class)
            ->getContentNodesData($contentNodeIds);
        if (!$contentNodesData) {
            return [];
        }

        $resolvedVariants = [];
        if ($contentVariantIds) {
            $resolvedVariants = $this->resolvedContentVariantsLoader->loadResolvedContentVariants($contentVariantIds);
        }

        $baseResolvedContentNodes = [];
        $resolvedContentNodes = [];
        foreach ($contentNodesData as $nodeData) {
            if (!empty($resolvedVariants[$nodeData['id']])) {
                $nodeData['contentVariant'] = reset($resolvedVariants[$nodeData['id']]);
            } else {
                $nodeData['contentVariant'] = new ResolvedContentVariant();
            }

            $resolvedContentNode = $this->resolvedContentNodeFactory->createFromArray($nodeData);

            if (isset($nodeData['parentNode']['id'], $resolvedContentNodes[$nodeData['parentNode']['id']])) {
                $resolvedContentNodes[$nodeData['parentNode']['id']]->addChildNode($resolvedContentNode);
            } else {
                $baseResolvedContentNodes[$nodeData['id']] = $resolvedContentNode;
            }

            $resolvedContentNodes[$nodeData['id']] = $resolvedContentNode;
        }

        return $baseResolvedContentNodes;
    }
}
