<?php

namespace Oro\Bundle\ProductBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\ProductBundle\Entity\CollectionSortOrder;

/**
 * Contains entity repository methods for {@see CollectionSortOrder}.
 */
class CollectionSortOrderRepository extends ServiceEntityRepository
{
    /**
     * @param int $segmentId
     * @param int[] $productIds
     *
     * @return array<CollectionSortOrder>
     */
    public function findBySegmentAndProductIds(int $segmentId, array $productIds): array
    {
        if (!$productIds) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('cso', 'cso.product');

        return $queryBuilder
            ->where($queryBuilder->expr()->eq('cso.segment', ':segment'))
            ->andWhere($queryBuilder->expr()->in('cso.product', ':products'))
            ->setParameter('segment', $segmentId, Types::INTEGER)
            ->setParameter('products', $productIds, Connection::PARAM_INT_ARRAY)
            ->getQuery()
            ->getResult();
    }

    public function removeBySegmentAndProductIds(int $segmentId, array $productIds): void
    {
        $sortOrderEntities = $this->findBySegmentAndProductIds($segmentId, $productIds);
        foreach ($sortOrderEntities as $sortOrderEntity) {
            $this->getEntityManager()->remove($sortOrderEntity);
        }
    }
}
