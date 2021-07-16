<?php

namespace Oro\Bundle\PromotionBundle\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\CouponUsage;

class CouponUsageManager
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param Coupon $coupon
     * @param CustomerUser $customerUser
     * @param bool $flush
     * @return CouponUsage
     */
    public function createCouponUsage(
        Coupon $coupon,
        CustomerUser $customerUser = null,
        $flush = false
    ) {
        $couponUsage = new CouponUsage();
        $couponUsage->setCoupon($coupon);
        $couponUsage->setPromotion($coupon->getPromotion());
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
        return $this->registry->getManagerForClass(CouponUsage::class);
    }
}
