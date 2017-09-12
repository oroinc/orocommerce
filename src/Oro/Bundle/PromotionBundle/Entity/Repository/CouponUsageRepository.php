<?php

namespace Oro\Bundle\PromotionBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\PromotionBundle\Entity\Coupon;

class CouponUsageRepository extends EntityRepository
{
    /**
     * @param Coupon $coupon
     * @param CustomerUser $customerUser
     * @return int
     */
    public function getCouponUsageCount(Coupon $coupon, CustomerUser $customerUser = null)
    {
        $queryBuilder = $this->createQueryBuilder('couponUsage');

        $queryBuilder->select($queryBuilder->expr()->count('couponUsage.id'))
            ->where($queryBuilder->expr()->eq('couponUsage.coupon', ':coupon'))
            ->setParameter('coupon', $coupon);

        if ($customerUser) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq('couponUsage.customerUser', ':customerUser'))
                ->setParameter('customerUser', $customerUser);
        }

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
