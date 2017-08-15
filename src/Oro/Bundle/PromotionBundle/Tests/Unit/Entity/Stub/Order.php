<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\OrderBundle\Entity\Order as BaseOrder;
use Oro\Bundle\PromotionBundle\Entity\Coupon;

class Order extends BaseOrder
{
    /**
     * @var Collection|Coupon[]
     */
    private $appliedCoupons;

    public function __construct()
    {
        parent::__construct();

        $this->appliedCoupons = new ArrayCollection();
    }


    /**
     * @return Collection|Coupon[]
     */
    public function getAppliedCoupons()
    {
        return $this->appliedCoupons;
    }

    /**
     * @param Coupon $coupon
     * @return bool
     */
    public function hasAppliedCoupon(Coupon $coupon)
    {
        return $this->appliedCoupons->contains($coupon);
    }

    /**
     * @param Coupon $appliedCoupon
     * @return Order
     */
    public function addAppliedCoupons(Coupon $appliedCoupon)
    {
        if (!$this->hasAppliedCoupon($appliedCoupon)) {
            $this->appliedCoupons[] = $appliedCoupon;
        }

        return $this;
    }
}
