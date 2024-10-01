<?php

namespace Oro\Bundle\WebCatalogBundle\Menu;

use Doctrine\ORM\Query\Expr;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\Loader\ResolvedContentNodesLoader;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Provides {@see ResolvedContentNode} for the specified {@see ContentNode}.
 */
class MenuContentNodesProvider implements MenuContentNodesProviderInterface
{
    private ManagerRegistry $managerRegistry;

    private ResolvedContentNodesLoader $resolvedContentNodesLoader;

    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(
        ManagerRegistry $managerRegistry,
        ResolvedContentNodesLoader $resolvedContentNodesLoader,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->resolvedContentNodesLoader = $resolvedContentNodesLoader;
        $this->authorizationChecker = $authorizationChecker;
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
    #[\Override]
    public function getResolvedContentNode(ContentNode $contentNode, array $context = []): ?ResolvedContentNode
    {
        if (!$this->authorizationChecker->isGranted(BasicPermission::VIEW, $contentNode->getWebCatalog())) {
            return null;
        }

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
