<?php

namespace Oro\Bundle\WebCatalogBundle\ContentNodeUtils;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CustomerBundle\Provider\ScopeCustomerGroupCriteriaProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\Loader\ResolvedContentNodesLoader;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Provider\ContentNodeProvider;

/**
 * Service that collect content nodes tree by scope, including content variants.
 */
class ContentNodeTreeResolver implements ContentNodeTreeResolverInterface
{
    private DoctrineHelper $doctrineHelper;

    private ContentNodeProvider $contentNodeProvider;

    private ScopeManager $scopeManager;

    private ResolvedContentNodesLoader $resolvedContentNodesLoader;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        ContentNodeProvider $contentNodeProvider,
        ScopeManager $scopeManager,
        ResolvedContentNodesLoader $resolvedContentNodesLoader
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->contentNodeProvider = $contentNodeProvider;
        $this->scopeManager = $scopeManager;
        $this->resolvedContentNodesLoader = $resolvedContentNodesLoader;
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
        if (!$scopes) {
            return null;
        }

        $variantIdByContentNodeIds = $this->getVariantIdByContentNodeIds($node, $scopes, $context);
        if (!$variantIdByContentNodeIds) {
            return null;
        }

        $resolvedContentNodes = $this->resolvedContentNodesLoader->loadResolvedContentNodes($variantIdByContentNodeIds);

        return $resolvedContentNodes[$node->getId()] ?? null;
    }

    /**
     * @param ContentNode $node
     * @param Scope|Scope[] $scopes
     * @param array $context
     *
     * @return array<int,int> $variantIdByContentNodeIds
     *  [
     *      int $nodeId => int $contentVariantId,
     *      // ...
     *  ]
     */
    private function getVariantIdByContentNodeIds(
        ContentNode $node,
        Scope|array $scopes,
        array $context = []
    ): array {
        $treeDepth = (int)($context['tree_depth'] ?? -1);
        $queryBuilder = $this->getContentNodeIdsQueryBuilder($node, $treeDepth);

        /**
         * @var array<array<int,int>> $variantIdByContentNodeIds
         *  [
         *      [
         *          int $nodeId => int $contentVariantId,
         *          // ...
         *      ],
         *      // ...
         *  ]
         */
        $variantIdByContentNodeIds = [];
        foreach ($scopes as $scope) {
            $criteria = $this->getCriteriaByScope($scope);
            $nodeIds = $this->contentNodeProvider->getContentNodeIds($queryBuilder, $criteria);
            if ($nodeIds) {
                $variantIdByContentNodeIds[] = $this->contentNodeProvider->getContentVariantIds($nodeIds, $criteria);
            }
        }

        // Flattens content variant ids collected for each scope, so content variant IDs are merged
        // as per the scopes ordering in $scopes, e.g. content node #1 with content variant #11 from $scopes[0]
        // has higher priority than content node #1 with content variant #12 from $scopes[1].
        return array_replace([], ...array_reverse($variantIdByContentNodeIds));
    }

    private function getContentNodeIdsQueryBuilder(ContentNode $node, int $treeDepth = -1): QueryBuilder
    {
        $qb = $this->doctrineHelper
            ->createQueryBuilder(ContentNode::class, 'node')
            ->where('node.left >= :left AND node.right <= :right')
            ->setParameter('left', $node->getLeft())
            ->setParameter('right', $node->getRight());

        if ($treeDepth > -1) {
            $qb
                ->andWhere('node.level <= :max_level')
                ->setParameter('max_level', $node->getLevel() + $treeDepth);
        }

        return $qb;
    }

    private function getCriteriaByScope(Scope $scope): ScopeCriteria
    {
        $context = [];
        // We need to use the customer group from the customer stored in the scope,
        // because the customer group may not exist in the scope (actually in the most cases the scope
        // does not contain the full information about the context - the context is represented
        // by the scope criteria object that is filled by scope criteria providers),
        // as we do not know how this scope was retrieved (e.g. it may be retrieved for a slug,
        // a consent or other object).
        // We need the customer group to be sure that content nodes that have a restriction
        // by a customer group will be filtered correctly.
        if (method_exists($scope, 'getCustomer') && $scope->getCustomer()) {
            $context[ScopeCustomerGroupCriteriaProvider::CUSTOMER_GROUP] = $scope->getCustomer()->getGroup();
        }

        return $this->scopeManager->getCriteriaByScope($scope, 'web_content', $context);
    }
}
