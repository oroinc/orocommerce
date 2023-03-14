<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub;

use Doctrine\Common\Collections\Collection;

trait AppliedPromotionTrait
{
    /**
     * @var Collection|AppliedPromotion[]
     */
    protected $appliedPromotions;

    /**
     * {@inheritdoc}
     */
    public function removeAppliedCoupon($coupon)
    {
        $this->appliedPromotions->removeElement($coupon);
    }

    /**
     * {@inheritdoc}
     */
    public function setAppliedCoupons($coupons)
    {
        $this->appliedPromotions = $coupons;
    }

    /**
     * {@inheritdoc}
     */
    public function getAppliedCoupons()
    {
        return $this->appliedPromotions;
    }

    /**
     * {@inheritdoc}
     */
    public function addAppliedCoupon($appliedCoupon)
    {
        return $this->appliedPromotions->add($appliedCoupon);
    }
}
