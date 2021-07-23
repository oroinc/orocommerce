<?php

namespace Oro\Bundle\PromotionBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;

class AppliedPromotionRepository extends EntityRepository
{
    /**
     * @param Order $order
     * @return AppliedPromotion[]
     */
    public function findByOrder(Order $order)
    {
        return $this->findBy(['order' => $order]);
    }

    public function getAppliedPromotionsInfo(Order $order): array
    {
        $queryBuilder = $this->createQueryBuilder('appliedPromotion');

        $queryBuilder
            ->select([
                'appliedPromotion.id',
                'appliedPromotion.promotionName',
                'appliedPromotion.sourcePromotionId',
                'appliedPromotion.active AS active',
                'appliedPromotion.type',
                'appliedCoupon.couponCode',
                'appliedDiscounts.currency AS currency',
                'SUM(appliedDiscounts.amount) AS amount'
            ])
            ->join('appliedPromotion.appliedDiscounts', 'appliedDiscounts')
            ->leftJoin('appliedPromotion.appliedCoupon', 'appliedCoupon')
            ->where($queryBuilder->expr()->eq('appliedPromotion.order', ':order'))
            ->groupBy(
                'appliedPromotion.id',
                'appliedPromotion.promotionName',
                'appliedPromotion.sourcePromotionId',
                'appliedPromotion.active',
                'appliedPromotion.type',
                'appliedCoupon.couponCode',
                'appliedDiscounts.currency'
            )
            ->orderBy('appliedPromotion.id')
            ->setParameter('order', $order);

        return $queryBuilder->getQuery()->getArrayResult();
    }

    public function removeAppliedPromotionsByOrder(Order $order)
    {
        $queryBuilder = $this->createQueryBuilder('appliedPromotion');
        $queryBuilder->delete()
            ->where($queryBuilder->expr()->eq('appliedPromotion.order', ':order'))
            ->setParameter('order', $order)
            ->getQuery()
            ->execute();
    }
}
