<?php

namespace Oro\Bundle\PromotionBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\PromotionBundle\Entity\Coupon;

class CouponRepository extends EntityRepository
{
    /**
     * @param array $ids
     * @return Coupon[]
     */
    public function getCouponsWithPromotionByIds(array $ids)
    {
        $queryBuilder = $this->createQueryBuilder('coupon');

        return $queryBuilder
            ->innerJoin('coupon.promotion', 'promotion')
            ->andWhere($queryBuilder->expr()->in('coupon.id', ':ids'))
            ->setParameter('ids', $ids)
            ->getQuery()->getResult();
    }
}
