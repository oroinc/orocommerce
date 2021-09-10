<?php

namespace Oro\Bundle\CatalogBundle\Api\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Normalizer\ObjectNormalizer;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\CriteriaConnector;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\QueryAclHelper;
use Oro\Bundle\CatalogBundle\Api\Model\CategoryNode;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * The repository to get master catalog tree nodes available for the storefront.
 */
class CategoryNodeRepository
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ObjectNormalizer */
    private $objectNormalizer;

    /** @var CriteriaConnector */
    private $criteriaConnector;

    /** @var QueryAclHelper */
    private $queryAclHelper;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        ObjectNormalizer $objectNormalizer,
        CriteriaConnector $criteriaConnector,
        QueryAclHelper $queryAclHelper
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->objectNormalizer = $objectNormalizer;
        $this->criteriaConnector = $criteriaConnector;
        $this->queryAclHelper = $queryAclHelper;
    }

    /**
     * Gets all nodes filtered by the given criteria and available for the storefront.
     *
     * @param Criteria               $criteria
     * @param EntityDefinitionConfig $config
     * @param array                  $normalizationContext
     *
     * @return array The normalized data for the requested nodes
     */
    public function getCategoryNodes(
        Criteria $criteria,
        EntityDefinitionConfig $config,
        array $normalizationContext
    ): array {
        $nodes = $this->getAvailableCategoryNodes($criteria, $config, $normalizationContext[Context::REQUEST_TYPE]);
        if (!$nodes) {
            return [];
        }

        return $this->objectNormalizer->normalizeObjects($nodes, $config, $normalizationContext);
    }

    /**
     * Gets a node by its ID.
     *
     * @param int                    $id
     * @param EntityDefinitionConfig $config
     * @param array                  $normalizationContext
     *
     * @return array|null The normalized data for the requested node or NULL if the node does not exist
     *
     * @throws AccessDeniedException if the requested node is not available for the storefront
     */
    public function getCategoryNode(
        int $id,
        EntityDefinitionConfig $config,
        array $normalizationContext
    ): ?array {
        $node = $this->getCategoryNodeEntity($id, $config, $normalizationContext[Context::REQUEST_TYPE]);
        if (null === $node) {
            return null;
        }

        $normalizedNodes = $this->objectNormalizer->normalizeObjects(
            [$node],
            $config,
            $normalizationContext
        );

        return reset($normalizedNodes);
    }

    /**
     * Gets a node entity by its ID.
     *
     * @param int                    $id
     * @param EntityDefinitionConfig $config
     * @param RequestType            $requestType
     *
     * @return CategoryNode|null The requested node or NULL if the node does not exist
     *
     * @throws AccessDeniedException if the requested node is not available for the storefront
     */
    public function getCategoryNodeEntity(
        int $id,
        EntityDefinitionConfig $config,
        RequestType $requestType
    ): ?CategoryNode {
        $node = $this->getAvailableCategoryNode($id, $config, $requestType);
        if (null === $node && $this->isCategoryNodeExist($id)) {
            throw new AccessDeniedException();
        }

        return $node;
    }

    /**
     * Checks the given node IDs and returns only IDs of nodes that are available for the storefront.
     *
     * @param int[]                  $ids
     * @param EntityDefinitionConfig $config
     * @param RequestType            $requestType
     *
     * @return int[]
     */
    public function getAvailableCategoryNodeIds(
        array $ids,
        EntityDefinitionConfig $config,
        RequestType $requestType
    ): array {
        $qb = $this->doctrineHelper->createQueryBuilder(Category::class, 'e')
            ->select('e.id')
            ->where('e.id IN (:ids)')
            ->setParameter('ids', $ids);
        $rows = $this->queryAclHelper->protectQuery($qb, $config, $requestType)
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[] = $row['id'];
        }

        return $result;
    }

    /**
     * @param Criteria               $criteria
     * @param EntityDefinitionConfig $config
     * @param RequestType            $requestType
     *
     * @return CategoryNode[]
     */
    private function getAvailableCategoryNodes(
        Criteria $criteria,
        EntityDefinitionConfig $config,
        RequestType $requestType
    ): array {
        $qb = $this->doctrineHelper->createQueryBuilder(Category::class, 'e')
            ->select('e.id')
            ->orderBy('e.left');
        $this->criteriaConnector->applyCriteria($qb, $criteria);
        $rows = $this->queryAclHelper->protectQuery($qb, $config, $requestType)
            ->getArrayResult();

        $nodes = [];
        foreach ($rows as $row) {
            $nodes[] = new CategoryNode($row['id']);
        }

        return $nodes;
    }

    private function getAvailableCategoryNode(
        int $id,
        EntityDefinitionConfig $config,
        RequestType $requestType
    ): ?CategoryNode {
        $qb = $this->createCategoryNodeQueryBuilder($id)
            ->select('e.id');
        $rows = $this->queryAclHelper->protectQuery($qb, $config, $requestType)
            ->getArrayResult();
        if (!$rows) {
            return null;
        }

        return new CategoryNode($id);
    }

    private function isCategoryNodeExist(int $id): bool
    {
        $rows = $this->createCategoryNodeQueryBuilder($id)
            ->getQuery()
            ->getArrayResult();

        return !empty($rows);
    }

    private function createCategoryNodeQueryBuilder(int $id): QueryBuilder
    {
        return $this->doctrineHelper->createQueryBuilder(Category::class, 'e')
            ->select('e.id')
            ->where('e = :id')
            ->setParameter('id', $id);
    }
}
