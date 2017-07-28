<?php

namespace  Oro\Bundle\PromotionBundle\CouponGeneration\Options;

use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;

/**
 * Simple DTO that used to transfer coupon generation options through different code layers.
 */
class CouponGenerationOptions extends CodeGenerationOptions
{
    /**
     * @var int
     */
    protected $couponQuantity;

    /**
     * @var Promotion
     */
    protected $promotion;

    /**
     * @var int
     */
    protected $usesPerCoupon = 1;

    /**
     * @var int
     */
    protected $usesPerUser = 1;

    /**
     * @var \DateTime
     */
    protected $expirationDate;

    /**
     * @var BusinessUnit
     */
    protected $owner;

    /**
     * @return int
     */
    public function getCouponQuantity()
    {
        return $this->couponQuantity;
    }

    /**
     * @param int $couponQuantity
     */
    public function setCouponQuantity($couponQuantity)
    {
        $this->couponQuantity = $couponQuantity;
    }

    /**
     * @return Promotion
     */
    public function getPromotion()
    {
        return $this->promotion;
    }

    /**
     * @param Promotion $promotion
     */
    public function setPromotion($promotion)
    {
        $this->promotion = $promotion;
    }

    /**
     * @return int
     */
    public function getUsesPerCoupon()
    {
        return $this->usesPerCoupon;
    }

    /**
     * @param int $usesPerCoupon
     */
    public function setUsesPerCoupon($usesPerCoupon)
    {
        $this->usesPerCoupon = $usesPerCoupon;
    }

    /**
     * @return int
     */
    public function getUsesPerUser()
    {
        return $this->usesPerUser;
    }

    /**
     * @param int $usersPerUser
     */
    public function setUsesPerUser($usersPerUser)
    {
        $this->usesPerUser = $usersPerUser;
    }

    /**
     * @return \DateTime
     */
    public function getExpirationDate()
    {
        return $this->expirationDate;
    }

    /**
     * @param \DateTime $expirationDate
     */
    public function setExpirationDate($expirationDate)
    {
        $this->expirationDate = $expirationDate;
    }

    /**
     * @return BusinessUnit|null
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param BusinessUnit|null $owner
     */
    public function setOwner(BusinessUnit $owner)
    {
        $this->owner = $owner;

        return $this;
    }
}
