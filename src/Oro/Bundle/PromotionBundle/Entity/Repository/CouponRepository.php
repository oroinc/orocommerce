<?php

namespace Oro\Bundle\PromotionBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Coupon ORM Entity repository.
 */
class CouponRepository extends ServiceEntityRepository
{
    private ?AclHelper $aclHelper = null;

    public function setAclHelper(AclHelper $aclHelper): void
    {
        $this->aclHelper = $aclHelper;
    }

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
     * @param int[]    $promotionIds
     * @param string[] $couponCodes
     *
     * @return int[]
     */
    public function getPromotionsWithMatchedCoupons(array $promotionIds, array $couponCodes): array
    {
        $queryBuilder = $this->createQueryBuilder('coupon');

        $result = $queryBuilder->select('DISTINCT IDENTITY(coupon.promotion) AS id')
            ->where($queryBuilder->expr()->in('IDENTITY(coupon.promotion)', ':promotions'))
            ->andWhere($queryBuilder->expr()->in('coupon.code', ':couponCodes'))
            ->andWhere($queryBuilder->expr()->eq('coupon.enabled', ':enabled'))
            ->setParameter('promotions', $promotionIds)
            ->setParameter('couponCodes', array_map('strval', $couponCodes)) //Ensure coupon codes are passed as strings
            ->setParameter('enabled', true)
            ->getQuery()->getArrayResult();

        return array_column($result, 'id');
    }

    public function getPromotionsWithMatchedCouponsIds(array $promotionIds, array $couponIds): array
    {
        $queryBuilder = $this->createQueryBuilder('coupon');

        $result = $queryBuilder->select('DISTINCT IDENTITY(coupon.promotion) AS id')
            ->where($queryBuilder->expr()->in('IDENTITY(coupon.promotion)', ':promotions'))
            ->andWhere($queryBuilder->expr()->in('coupon.id', ':couponIds'))
            ->andWhere($queryBuilder->expr()->eq('coupon.enabled', ':enabled'))
            ->setParameter('promotions', $promotionIds)
            ->setParameter('couponIds', $couponIds)
            ->setParameter('enabled', true)
            ->getQuery()->getArrayResult();

        return array_column($result, 'id');
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

    /**
     * @param string $couponCode
     * @param bool $caseInsensitive
     * @return Coupon|null
     */
    public function getSingleCouponByCode(string $couponCode, bool $caseInsensitive = false)
    {
        $result = $this->getCouponByCode($couponCode, $caseInsensitive);

        if (!$result || count($result) !== 1) {
            return null;
        }
        $coupon = reset($result);

        // MySQL does not perform case-sensitive search by default, this check added to make search platform independent
        if ($coupon && !$caseInsensitive && $coupon->getCode() !== $couponCode) {
            return null;
        }

        return $coupon;
    }
}
