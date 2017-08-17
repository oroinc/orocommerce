<?php

namespace Oro\Bundle\PromotionBundle\Entity;

use Doctrine\Common\Collections\Collection;

interface AppliedCouponAwareInterface
{
    /**
     * @param Coupon $coupon
     */
    public function addAppliedCoupon($coupon);

    /**
     * @return Collection
     */
    public function getAppliedCoupons();

    /**
     * @param Coupon $coupon
     */
    public function removeAppliedCoupon($coupon);

    /**
     * @param array|Collection|\Traversable|\ArrayAccess $coupons
     * @return $this
     */
    public function setAppliedCoupons($coupons);
}
