<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub;

use Doctrine\Common\Collections\Collection;

trait AppliedPromotionTrait
{
    /**
     * @var Collection|AppliedPromotion[]
     */
    protected ?Collection $appliedPromotions = null;

    public function removeAppliedCoupon($coupon)
    {
        $this->appliedPromotions->removeElement($coupon);
    }

    public function setAppliedCoupons($coupons)
    {
        $this->appliedPromotions = $coupons;
    }

    public function getAppliedCoupons()
    {
        return $this->appliedPromotions;
    }

    public function addAppliedCoupon($appliedCoupon)
    {
        return $this->appliedPromotions->add($appliedCoupon);
    }
}
