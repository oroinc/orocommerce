<?php

namespace Oro\Bundle\PromotionBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Promotion;

/**
 * Coupon ORM Entity repository.
 */
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

    /**
     * @param string $couponCode
     * @param bool $caseInsensitive
     * @return Coupon|null
     */
    public function getSingleCouponByCode(string $couponCode, bool $caseInsensitive = false)
    {
        $qb = $this->createQueryBuilder('coupon');
        if ($caseInsensitive) {
            $field = 'coupon.codeUppercase';
            $code = strtoupper($couponCode);
        } else {
            $field = 'coupon.code';
            $code = $couponCode;
        }

        $qb->where($qb->expr()->eq($field, ':code'))
            ->setParameter('code', $code);

        /** @var Coupon[] $result */
        $result = $qb->getQuery()->getResult();
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
