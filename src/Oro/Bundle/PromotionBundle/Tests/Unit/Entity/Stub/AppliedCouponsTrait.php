<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;

trait AppliedCouponsTrait
{
    /**
     * @var Collection|AppliedCoupon[]
     */
    protected $appliedCoupons;

    /**
     * {@inheritdoc}
     */
    public function removeAppliedCoupon($coupon)
    {
        $this->appliedCoupons->removeElement($coupon);
    }

    /**
     * {@inheritdoc}
     */
    public function setAppliedCoupons($coupons)
    {
        $this->appliedCoupons = $coupons;
    }

    /**
     * {@inheritdoc}
     */
    public function getAppliedCoupons()
    {
        return $this->appliedCoupons;
    }

    /**
     * {@inheritdoc}
     */
    public function addAppliedCoupon($appliedCoupon)
    {
        return $this->appliedCoupons->add($appliedCoupon);
    }
}
