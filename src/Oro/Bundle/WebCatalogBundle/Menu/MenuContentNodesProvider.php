<?php

namespace Oro\Bundle\WebCatalogBundle\Menu;

use Doctrine\ORM\Query\Expr;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\Loader\ResolvedContentNodesLoader;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

/**
 * Provides {@see ResolvedContentNode} for the specified {@see ContentNode}.
 */
class MenuContentNodesProvider implements MenuContentNodesProviderInterface
{
    private ManagerRegistry $managerRegistry;

    private ResolvedContentNodesLoader $resolvedContentNodesLoader;

    public function __construct(
        ManagerRegistry $managerRegistry,
        ResolvedContentNodesLoader $resolvedContentNodesLoader,
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->resolvedContentNodesLoader = $resolvedContentNodesLoader;
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
        $contentNodeIds = $this->managerRegistry
            ->getRepository(ContentNode::class)
            ->getContentNodePlainTreeQueryBuilder($contentNode, $context['tree_depth'] ?? -1)
            ->innerJoin('node.contentVariants', 'contentVariant', Expr\Join::WITH, 'contentVariant.default = true')
            ->select('node.id as node_id', 'contentVariant.id as variant_id')
            ->getQuery()
            ->getArrayResult();

        if (!$contentNodeIds) {
            return null;
        }

        $contentNodeIds = array_column($contentNodeIds, 'variant_id', 'node_id');
        $resolvedContentNodes = $this->resolvedContentNodesLoader->loadResolvedContentNodes($contentNodeIds);

        return $resolvedContentNodes[$contentNode->getId()] ?? null;
    }
}
