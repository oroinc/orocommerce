<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\OrderBundle\Entity\Order as BaseOrder;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscountsAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\Coupon;

class Order extends BaseOrder implements AppliedDiscountsAwareInterface
{
    /**
     * @var Collection|Coupon[]
     */
    private $appliedCoupons;

    /**
     * @var Collection|AppliedDiscount[]
     */
    private $appliedDiscounts;

    public function __construct()
    {
        parent::__construct();

        $this->appliedCoupons = new ArrayCollection();
        $this->appliedDiscounts = new ArrayCollection();
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

    /**
     * @param Coupon $appliedCoupon
     * @return Order
     */
    public function addAppliedCoupon(Coupon $appliedCoupon)
    {
        return $this->addAppliedCoupons($appliedCoupon);
    }

    /**
     * {@inheritdoc}
     */
    public function addAppliedDiscount($discount)
    {
        $this->appliedDiscounts->add($discount);
    }

    /**
     * {@inheritdoc}
     */
    public function removeAppliedDiscount($discount)
    {
        $this->appliedDiscounts->remove($discount);
    }

    /**
     * {@inheritdoc}
     */
    public function setAppliedDiscounts($discounts)
    {
        $this->appliedDiscounts = $discounts;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAppliedDiscounts()
    {
        return $this->appliedDiscounts;
    }
}
