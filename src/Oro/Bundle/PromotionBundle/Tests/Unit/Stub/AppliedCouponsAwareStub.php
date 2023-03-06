<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Stub;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;

class AppliedCouponsAwareStub
{
    /**
     * @param AppliedCoupon $coupon
     */
    public function addAppliedCoupon($coupon)
    {
    }

    /**
     * @return Collection|AppliedCoupon[]
     */
    public function getAppliedCoupons()
    {
    }

    /**
     * @param AppliedCoupon $coupon
     */
    public function removeAppliedCoupon($coupon)
    {
    }

    /**
     * @param AppliedCoupon[] $coupons
     * @return $this
     */
    public function setAppliedCoupons($coupons)
    {
    }
}
