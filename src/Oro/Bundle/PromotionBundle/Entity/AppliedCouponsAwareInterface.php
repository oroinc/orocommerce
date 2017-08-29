<?php

namespace Oro\Bundle\PromotionBundle\Entity;

use Doctrine\Common\Collections\Collection;

interface AppliedCouponsAwareInterface
{
    /**
     * @param AppliedCoupon $coupon
     */
    public function addAppliedCoupon($coupon);

    /**
     * @return Collection|AppliedCoupon[]
     */
    public function getAppliedCoupons();

    /**
     * @param AppliedCoupon $coupon
     */
    public function removeAppliedCoupon($coupon);

    /**
     * @param AppliedCoupon[] $coupons
     * @return $this
     */
    public function setAppliedCoupons($coupons);
}
