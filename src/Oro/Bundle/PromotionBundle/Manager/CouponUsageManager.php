<?php

namespace Oro\Bundle\PromotionBundle\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\CouponUsage;
use Oro\Bundle\PromotionBundle\Entity\Promotion;

class CouponUsageManager
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param Coupon $coupon
     * @param Promotion $promotion
     * @param CustomerUser $customerUser
     * @param bool $flush
     * @return CouponUsage
     */
    public function createCouponUsage(
        Coupon $coupon,
        Promotion $promotion,
        CustomerUser $customerUser = null,
        $flush = false
    ) {
        $couponUsage = new CouponUsage();
        $couponUsage->setCoupon($coupon);
        $couponUsage->setPromotion($promotion);
        $couponUsage->setCustomerUser($customerUser);

        $em = $this->getCouponUsageEntityManager();
        $em->persist($couponUsage);

        if ($flush) {
            $em->flush($couponUsage);
        }

        return $couponUsage;
    }

    /**
     * @param Coupon $coupon
     * @return int
     */
    public function getCouponUsageCount(Coupon $coupon)
    {
        return $this->getCouponUsageEntityManager()
            ->getRepository(CouponUsage::class)
            ->getCouponUsageCount($coupon);
    }

    /**
     * @param Coupon $coupon
     * @param $customerUser
     * @return int
     */
    public function getCouponUsageCountByCustomerUser(Coupon $coupon, CustomerUser $customerUser)
    {
        return $this->getCouponUsageEntityManager()
            ->getRepository(CouponUsage::class)
            ->getCouponUsageCount($coupon, $customerUser);
    }

    /**
     * @return EntityManager
     */
    private function getCouponUsageEntityManager()
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->registry->getManagerForClass(CouponUsage::class);

        return $entityManager;
    }
}
