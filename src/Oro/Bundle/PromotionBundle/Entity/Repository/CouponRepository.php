<?php

namespace Oro\Bundle\PromotionBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Promotion;

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
     * @param array|int[]|Promotion[] $promotions
     * @param array $couponCodes|string[]
     *
     * @return array
     */
    public function getPromotionsWithMatchedCoupons(array $promotions, array $couponCodes): array
    {
        $queryBuilder = $this->createQueryBuilder('coupon');

        $result = $queryBuilder->select('DISTINCT IDENTITY(coupon.promotion) AS id')
            ->where($queryBuilder->expr()->in('IDENTITY(coupon.promotion)', ':promotions'))
            ->andWhere($queryBuilder->expr()->in('coupon.code', ':couponCodes'))
            ->andWhere($queryBuilder->expr()->eq('coupon.enabled', ':enabled'))
            ->setParameter('promotions', $promotions)
            ->setParameter('couponCodes', array_map('strval', $couponCodes)) //Ensure coupon codes are passed as strings
            ->setParameter('enabled', true)
            ->getQuery()->getArrayResult();

        return array_column($result, 'id');
    }
}
