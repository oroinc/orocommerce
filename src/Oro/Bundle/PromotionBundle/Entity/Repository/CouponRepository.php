<?php

namespace Oro\Bundle\PromotionBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\PromotionBundle\Entity\Coupon;

/**
 * Doctrine repository for Coupon entity.
 */
class CouponRepository extends EntityRepository
{
    /**
     * @param int[] $ids
     *
     * @return Coupon[]
     */
    public function getCouponsWithPromotionByIds(array $ids): array
    {
        return $this->createQueryBuilder('coupon')
            ->innerJoin('coupon.promotion', 'promotion')
            ->andWhere('coupon.id IN(:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param int[]    $promotionIds
     * @param string[] $couponIds
     *
     * @return int[]
     */
    public function getPromotionsWithMatchedCoupons(array $promotionIds, array $couponIds): array
    {
        $rows =  $this->createQueryBuilder('coupon')
            ->select('DISTINCT IDENTITY(coupon.promotion) AS id')
            ->where('coupon.promotion IN(:promotions)')
            ->andWhere('coupon.id IN(:couponIds)')
            ->andWhere('coupon.enabled = :enabled')
            ->setParameter('promotions', $promotionIds)
            ->setParameter('couponIds', $couponIds)
            ->setParameter('enabled', true)
            ->getQuery()
            ->getArrayResult();

        return array_column($rows, 'id');
    }

    public function getSingleCouponByCode(string $couponCode, bool $caseInsensitive = false): ?Coupon
    {
        /** @var Coupon[] $coupons */
        $coupons = $this->createQueryBuilder('coupon')
            ->where(sprintf('coupon.%s = :code', $caseInsensitive ? 'codeUppercase' : 'code'))
            ->setParameter('code', $caseInsensitive ? strtoupper($couponCode) : $couponCode)
            ->getQuery()
            ->getResult();

        return \count($coupons) === 1
            ? reset($coupons)
            : null;
    }
}
