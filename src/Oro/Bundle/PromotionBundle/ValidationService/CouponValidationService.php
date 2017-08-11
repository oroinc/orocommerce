<?php

namespace Oro\Bundle\PromotionBundle\ValidationService;

use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Manager\CouponUsageManager;

class CouponValidationService
{
    /**
     * @var CouponUsageManager
     */
    private $couponUsageManager;

    /**
     * @param CouponUsageManager $couponUsageManager
     */
    public function __construct(CouponUsageManager $couponUsageManager)
    {
        $this->couponUsageManager = $couponUsageManager;
    }

    /**
     * @param Coupon $coupon
     * @return array|bool
     */
    public function getViolations(Coupon $coupon)
    {
        $violations = [];

        if (!$coupon->getPromotion()) {
            $violations[] = 'oro.promotion.coupon.violation.absent_promotion';
        }

        if ($this->isCouponExpired($coupon)) {
            $violations[] = 'oro.promotion.coupon.violation.expired';
        }

        if ($this->isCouponUsageLimitExceeded($coupon)) {
            $violations[] = 'oro.promotion.coupon.violation.usage_limit_exceeded';
        }

        return $violations;
    }

    /**
     * @param Coupon $coupon
     * @return bool
     */
    private function isCouponExpired(Coupon $coupon)
    {
        return $coupon->getValidUntil() && $coupon->getValidUntil() < new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @param Coupon $coupon
     * @return bool
     */
    private function isCouponUsageLimitExceeded(Coupon $coupon)
    {
        return $coupon->getUsesPerCoupon() <= $this->couponUsageManager->getCouponUsageCount($coupon);
    }
}
