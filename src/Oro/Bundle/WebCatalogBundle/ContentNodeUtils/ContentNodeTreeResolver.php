<?php

namespace Oro\Bundle\WebCatalogBundle\ContentNodeUtils;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\CustomerBundle\Provider\ScopeCustomerGroupCriteriaProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Provider\ContentNodeProvider;

/**
 * Service that collect content nodes tree by scope, including content variants.
 */
class ContentNodeTreeResolver implements ContentNodeTreeResolverInterface
{
    private const ROOT_NODE_IDENTIFIER = 'root';
    private const IDENTIFIER_GLUE      = '__';

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ContentNodeProvider */
    private $contentNodeProvider;

    /** @var ScopeManager */
    private $scopeManager;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        ContentNodeProvider $contentNodeProvider,
        ScopeManager $scopeManager
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->contentNodeProvider = $contentNodeProvider;
        $this->scopeManager = $scopeManager;
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
        $treeDepth = (int) ($context['tree_depth'] ?? -1);

        $criteriaByScope = [];
        $nodeIdsByScope = [];
        foreach ($scopes as $scope) {
            $criteria = $this->getCriteriaByScope($scope);
            $criteriaByScope[$scope->getId()] = $criteria;
            $nodeIdsByScope[$scope->getId()] = $this->loadContentNodeIds($node, $criteria, $treeDepth);
        }
        $nodeIdsByScope = array_filter($nodeIdsByScope);

        /** @var int[] $nodeIdsByScope */
        if (!$nodeIdsByScope) {
            return null;
        }

        $nodeIds = array_unique(array_merge(...$nodeIdsByScope));
        $nodes = $this->loadContentNodes($nodeIds);
        $variants = $this->loadContentVariants($nodeIdsByScope, $criteriaByScope);
        /** @var ClassMetadata $variantMetadata */
        $variantMetadata = $this->doctrineHelper->getEntityMetadataForClass(ContentVariant::class);

        $rootNodeId = array_shift($nodeIds);
        $resolvedRootNode = $this->createResolvedContentNode($rootNodeId, $nodes, $variants, $variantMetadata);
        if (null === $resolvedRootNode) {
            return null;
        }

        /** @var ResolvedContentNode[] $resolvedNodes */
        $resolvedNodes = [];
        $resolvedNodes[$resolvedRootNode->getId()] = $resolvedRootNode;
        foreach ($nodeIds as $nodeId) {
            $parentNodeId = $nodes[$nodeId]->getParentNode()->getId();
            if (isset($resolvedNodes[$parentNodeId])) {
                $resolvedNode = $this->createResolvedContentNode($nodeId, $nodes, $variants, $variantMetadata);
                if (null !== $resolvedNode) {
                    $resolvedNodes[$resolvedNode->getId()] = $resolvedNode;
                    $resolvedNodes[$parentNodeId]->addChildNode($resolvedNode);
                }
            }
        }

        return $resolvedRootNode;
    }

    /**
     * @param ContentNode $node
     * @param ScopeCriteria $criteria
     * @param int $treeDepth
     *
     * @return int[]
     */
    private function loadContentNodeIds(ContentNode $node, ScopeCriteria $criteria, int $treeDepth = -1): array
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

        return $this->contentNodeProvider->getContentNodeIds($qb, $criteria);
    }

    /**
     * @param int[] $nodeIds
     *
     * @return ContentNode[] [node id => content node, ...]
     */
    private function loadContentNodes(array $nodeIds): array
    {
        return $this->doctrineHelper
            ->createQueryBuilder(ContentNode::class, 'node', 'node.id')
            ->where('node.id IN (:ids)')
            ->setParameter('ids', array_values($nodeIds))
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array<int,int[]> $nodeIdsByScope
     *     [
     *          int $scopeId => int[] $nodeIds,
     *          // ...
     *     ]
     * @param array<int,ScopeCriteria> $criteriaByScope
     *     [
     *          int $scopeId => ScopeCriteria,
     *          // ...
     *     ]
     *
     * @return array<int,ContentVariant>
     *     [
     *          int $nodeId => ContentVariant,
     *          // ...
     *     ]
     */
    private function loadContentVariants(array $nodeIdsByScope, array $criteriaByScope): array
    {
        $contentVariantsByScope = [];
        foreach ($nodeIdsByScope as $scopeId => $nodeIds) {
            $variantIds = $this->contentNodeProvider->getContentVariantIds($nodeIds, $criteriaByScope[$scopeId]);

            /** @var ContentVariant[] $contentVariants */
            $contentVariants = $this->doctrineHelper
                ->createQueryBuilder(ContentVariant::class, 'variant', 'variant.id')
                ->where('variant.id IN (:ids)')
                ->setParameter('ids', array_values($variantIds))
                ->getQuery()
                ->getResult();

            $contentVariantsByScope[$scopeId] = [];
            foreach ($variantIds as $nodeId => $variantId) {
                if (isset($contentVariants[$variantId])) {
                    $contentVariantsByScope[$scopeId][$nodeId] = $contentVariants[$variantId];
                }
            }
        }

        return array_replace([], ...array_reverse($contentVariantsByScope));
    }

    /**
     * @param int              $nodeId
     * @param ContentNode[]    $nodes    [node id => content node, ...]
     * @param ContentVariant[] $variants [node id => content variant, ...]
     * @param ClassMetadata    $variantMetadata
     *
     * @return ResolvedContentNode|null
     */
    private function createResolvedContentNode(
        int $nodeId,
        array $nodes,
        array $variants,
        ClassMetadata $variantMetadata
    ): ?ResolvedContentNode {
        if (!isset($nodes[$nodeId], $variants[$nodeId])) {
            return null;
        }

        $node = $nodes[$nodeId];

        return new ResolvedContentNode(
            $nodeId,
            $this->getIdentifier($node),
            $node->getLeft(), // The "left" tree option used as the priority of the menu item.
            $node->getTitles(),
            $this->createResolvedContentVariant($variants[$nodeId], $variantMetadata),
            $node->isRewriteVariantTitle()
        );
    }

    private function createResolvedContentVariant(
        ContentVariant $variant,
        ClassMetadata $metadata
    ): ResolvedContentVariant {
        $resolvedVariant = new ResolvedContentVariant();
        foreach ($metadata->getFieldNames() as $fieldName) {
            $resolvedVariant->{$fieldName} = $metadata->getFieldValue($variant, $fieldName);
        }

        foreach ($metadata->getAssociationNames() as $associationName) {
            $associatedValue = $metadata->getFieldValue($variant, $associationName);
            if ($associationName === 'slugs') {
                $this->fillSlugs($associatedValue, $resolvedVariant);
            }
            if ($associatedValue instanceof Collection || $associatedValue instanceof ContentNode) {
                continue;
            }
            if ($associatedValue) {
                $resolvedVariant->{$associationName} = $associatedValue;
            }
        }

        return $resolvedVariant;
    }

    private function getIdentifier(ContentNode $node): string
    {
        /** @var LocalizedFallbackValue $localizedUrl */
        $localizedUrl = $node->getLocalizedUrls()
            ->filter(function (LocalizedFallbackValue $localizedUrl) {
                return $localizedUrl->getLocalization() === null;
            })
            ->first();
        if (!$localizedUrl) {
            $localizedUrl = $node->getLocalizedUrls()->first();
        }
        if (!$localizedUrl) {
            return '';
        }

        $url = trim($localizedUrl->getText(), '/');
        $identifierParts = [self::ROOT_NODE_IDENTIFIER];
        if ($url) {
            if (strpos($url, '/') > 0) {
                $identifierParts = array_merge($identifierParts, explode('/', $url));
            } else {
                $identifierParts[] = $url;
            }
        }

        return implode(self::IDENTIFIER_GLUE, $identifierParts);
    }

    /**
     * @param Collection<Slug> $slugs
     * @param ResolvedContentVariant $resolvedVariant
     */
    private function fillSlugs(Collection $slugs, ResolvedContentVariant $resolvedVariant): void
    {
        foreach ($slugs as $slug) {
            $localizedUrl = new LocalizedFallbackValue();
            $localizedUrl->setString($slug->getUrl());
            $localizedUrl->setLocalization($slug->getLocalization());

            $resolvedVariant->addLocalizedUrl($localizedUrl);
        }
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
