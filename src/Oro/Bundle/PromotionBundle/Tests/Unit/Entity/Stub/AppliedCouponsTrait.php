<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;

trait AppliedCouponsTrait
{
    /**
     * @var Collection|AppliedCoupon[]
     */
    protected ?Collection $appliedCoupons = null;

    public function removeAppliedCoupon($coupon)
    {
        $this->appliedCoupons->removeElement($coupon);
    }

    public function setAppliedCoupons($coupons)
    {
        $this->appliedCoupons = $coupons;
    }

    public function getAppliedCoupons()
    {
        return $this->appliedCoupons;
    }

    public function addAppliedCoupon($appliedCoupon)
    {
        return $this->appliedCoupons->add($appliedCoupon);
    }
}
