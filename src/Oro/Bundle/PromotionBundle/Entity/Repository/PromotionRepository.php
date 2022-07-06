<?php

namespace Oro\Bundle\PromotionBundle\Entity\Repository;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\SegmentBundle\Entity\Segment;

/**
 * Doctrine repository for Promotion entity
 */
class PromotionRepository extends EntityRepository
{
    /**
     * @param Segment $segment
     * @return object|Promotion|null
     */
    public function findPromotionByProductSegment(Segment $segment)
    {
        return $this->findOneBy(['productsSegment' => $segment]);
    }

    /**
     * @param array|Collection $ids
     * @return array
     */
    public function getPromotionsWithLabelsByIds($ids)
    {
        $qb = $this->createQueryBuilder('p');
        $qb->join('p.rule', 'rule')
            ->leftJoin('p.labels', 'labels')
            ->where($qb->expr()->in('p.id', ':ids'))
            ->setParameter('ids', $ids);

        $result = [];
        /** @var Promotion $promotion */
        foreach ($qb->getQuery()->getResult() as $promotion) {
            $result[$promotion->getId()] = $promotion;
        }

        return $result;
    }

    /**
     * @param int[] $promotionIds
     *
     * @return array
     *  [
     *      42 => 'Promotion name',
     *      // ...
     *  ]
     */
    public function getPromotionsNamesByIds(array $promotionIds): array
    {
        if (!$promotionIds) {
            return [];
        }

        $qb = $this->createQueryBuilder('p');
        $qb
            ->select('p.id', 'rule.name')
            ->join('p.rule', 'rule')
            ->where($qb->expr()->in('p.id', ':ids'))
            ->setParameter('ids', array_filter($promotionIds), Connection::PARAM_INT_ARRAY);

        return array_column($qb->getQuery()->getArrayResult(), 'name', 'id');
    }
}
