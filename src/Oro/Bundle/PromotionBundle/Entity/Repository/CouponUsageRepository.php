<?php

namespace Oro\Bundle\PromotionBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\PromotionBundle\Entity\Coupon;

/**
 * Repository for {@see \Oro\Bundle\PromotionBundle\Entity\CouponUsage} entities.
 */
class CouponUsageRepository extends EntityRepository
{
    /**
     * @param Coupon $coupon
     * @param CustomerUser|null $customerUser
     * @return int
     */
    public function getCouponUsageCount(Coupon $coupon, ?CustomerUser $customerUser = null)
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

    public function deleteCouponUsage(?array $couponIds, ?CustomerUser $customerUser): void
    {
        if (!$couponIds) {
            return;
        }

        $sub = $this->createQueryBuilder('cu_sub');
        $sub->select($sub->expr()->max('cu_sub.id'))
            ->where($sub->expr()->in('IDENTITY(cu_sub.coupon)', ':couponIds'))
            ->groupBy('cu_sub.coupon');

        if ($customerUser) {
            $sub->andWhere($sub->expr()->eq('cu_sub.customerUser', ':customerUser'));
        } else {
            $sub->andWhere($sub->expr()->isNull('cu_sub.customerUser'));
        }

        $outerQb = $this->createQueryBuilder('cu')
            ->delete()
            ->where($sub->expr()->in('cu.id', $sub->getDQL()))
            ->setParameter('couponIds', $couponIds);

        if ($customerUser) {
            $outerQb->setParameter('customerUser', $customerUser);
        }

        $outerQb->getQuery()->execute();
    }
}
