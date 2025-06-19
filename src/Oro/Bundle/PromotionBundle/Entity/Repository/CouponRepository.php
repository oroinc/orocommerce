<?php

namespace Oro\Bundle\PromotionBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Doctrine repository for Coupon entity.
 */
class CouponRepository extends ServiceEntityRepository
{
    private ?AclHelper $aclHelper = null;

    public function setAclHelper(AclHelper $aclHelper): void
    {
        $this->aclHelper = $aclHelper;
    }

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

    public function hasDuplicatesInCaseInsensitiveMode(): bool
    {
        $qb = $this->createQueryBuilder('coupon')
            ->select('1')
            ->groupBy('coupon.codeUppercase')
            ->having('COUNT(coupon.id) > 1')
            ->setMaxResults(1);

        return (bool) $this->aclHelper->apply($qb)->getOneOrNullResult();
    }

    public function getCouponByCode(string $couponCode, bool $caseInsensitive = false): array
    {
        /** @var Coupon[] $coupons */
        $qb = $this->createQueryBuilder('coupon')
            ->where(sprintf('coupon.%s = :code', $caseInsensitive ? 'codeUppercase' : 'code'))
            ->setParameter('code', $caseInsensitive ? strtoupper($couponCode) : $couponCode);

        return $this->aclHelper->apply($qb)->getResult();
    }

    public function getSingleCouponByCode(string $couponCode, bool $caseInsensitive = false): ?Coupon
    {
        $coupons = $this->getCouponByCode($couponCode, $caseInsensitive);

        return \count($coupons) === 1
            ? reset($coupons)
            : null;
    }
}
