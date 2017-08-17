<?php

namespace Oro\Bundle\PromotionBundle\Entity;

interface AppliedCouponsAwareInterface
{
    /**
     * @param Coupon $coupon
     */
    public function addAppliedCoupon($coupon);

    /**
     * @return Coupon[]
     */
    public function getAppliedCoupons();

    /**
     * @param Coupon $coupon
     */
    public function removeAppliedCoupon($coupon);

    /**
     * @param Coupon[] $coupons
     * @return $this
     */
    public function setAppliedCoupons($coupons);
}
