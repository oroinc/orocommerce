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

    /**
     * @param array $promotionsIds
     * @param array $couponCodes
     *
     * @return array
     */
    public function getPromotionsWithMatchedCoupons(array $promotionsIds, array $couponCodes)
    {
        $queryBuilder = $this->createQueryBuilder('coupon');

        $result = $queryBuilder->select('DISTINCT IDENTITY(coupon.promotion) AS id')
            ->where($queryBuilder->expr()->in('IDENTITY(coupon.promotion)', ':promotionIds'))
            ->andWhere($queryBuilder->expr()->in('coupon.code', ':couponCodes'))
            ->setParameter('promotionIds', $promotionsIds)
            ->setParameter('couponCodes', $couponCodes)
            ->getQuery()->getArrayResult();

        return array_column($result, 'id');
    }
}
