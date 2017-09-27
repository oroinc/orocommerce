<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList as BaseShoppingList;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\AppliedCouponsAwareInterface;

class ShoppingList extends BaseShoppingList implements AppliedCouponsAwareInterface
{
    /**
     * @var Collection|AppliedCoupon[]
     */
    private $appliedCoupons;

    public function __construct()
    {
        parent::__construct();

        $this->appliedCoupons = new ArrayCollection();
    }

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
