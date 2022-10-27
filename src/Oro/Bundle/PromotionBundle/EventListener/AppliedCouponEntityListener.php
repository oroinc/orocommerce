<?php

namespace Oro\Bundle\PromotionBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Manager\CouponUsageManager;

/**
 * This listener create CouponUsage entity after AppliedCoupon entity create.
 * In this way the number of coupons used is calculated.
 */
class AppliedCouponEntityListener
{
    /**
     * @var CouponUsageManager
     */
    private $couponUsageManager;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    public function __construct(CouponUsageManager $couponUsageManager, ManagerRegistry $registry)
    {
        $this->couponUsageManager = $couponUsageManager;
        $this->registry = $registry;
    }

    public function postPersist(AppliedCoupon $appliedCoupon)
    {
        $coupon = $this->registry->getManagerForClass(Coupon::class)
            ->find(Coupon::class, $appliedCoupon->getSourceCouponId());

        if (!$coupon) {
            return;
        }

        if ($appliedCoupon->getOrder()) {
            $this->couponUsageManager
                ->createCouponUsage($coupon, $appliedCoupon->getOrder()->getCustomerUser(), true);
        }
    }
}
