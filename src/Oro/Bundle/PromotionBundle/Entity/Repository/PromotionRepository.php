<?php

namespace Oro\Bundle\PromotionBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\SegmentBundle\Entity\Segment;

/**
 * The repository for Promotion entity.
 */
class PromotionRepository extends ServiceEntityRepository
{
    /**
     * @return Promotion[]
     */
    public function getAllPromotions(int $organizationId): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.organization = :organizationId')
            ->setParameter('organizationId', $organizationId);

        return $qb->getQuery()->getResult();
    }

    public function findPromotionByProductSegment(Segment $segment): ?Promotion
    {
        return $this->findOneBy(['productsSegment' => $segment]);
    }

    /**
     * @param int[] $ids
     *
     * @return Promotion[] [promotion id => promotion, ...]
     */
    public function getPromotionsWithLabelsByIds(array $ids): array
    {
        /** @var Promotion[] $promotions */
        $promotions = $this->createQueryBuilder('p')
            ->join('p.rule', 'rule')
            ->leftJoin('p.labels', 'labels')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($promotions as $promotion) {
            $result[$promotion->getId()] = $promotion;
        }

        return $result;
    }

    /**
     * @param int[] $ids
     *
     * @return string[] [promotion id => promotion name, ...]
     */
    public function getPromotionsNamesByIds(array $ids): array
    {
        if (!$ids) {
            return [];
        }

        $rows = $this->createQueryBuilder('p')
            ->select('p.id', 'rule.name')
            ->join('p.rule', 'rule')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', array_filter($ids), Connection::PARAM_INT_ARRAY)
            ->getQuery()
            ->getArrayResult();

        return array_column($rows, 'name', 'id');
    }
}
