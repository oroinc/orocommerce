<?php

namespace Oro\Bundle\PromotionBundle\CouponGeneration\Options;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\PromotionBundle\Entity\Promotion;

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
     * @var bool
     */
    protected $enabled = false;

    /**
     * @var int
     */
    protected $usesPerCoupon = 1;

    /**
     * @var int
     */
    protected $usesPerPerson = 1;

    /**
     * @var \DateTime
     */
    protected $validFrom;

    /**
     * @var \DateTime
     */
    protected $validUntil;

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

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled)
    {
        $this->enabled = $enabled;
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
    public function getUsesPerPerson()
    {
        return $this->usesPerPerson;
    }

    /**
     * @param int $usersPerUser
     */
    public function setUsesPerPerson($usersPerUser)
    {
        $this->usesPerPerson = $usersPerUser;
    }

    /**
     * @return \DateTime
     */
    public function getValidFrom()
    {
        return $this->validFrom;
    }

    /**
     * @param \DateTime $validFrom
     */
    public function setValidFrom($validFrom)
    {
        $this->validFrom = $validFrom;
    }

    /**
     * @return \DateTime
     */
    public function getValidUntil()
    {
        return $this->validUntil;
    }

    /**
     * @param \DateTime $validUntil
     */
    public function setValidUntil($validUntil)
    {
        $this->validUntil = $validUntil;
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
     *
     * @return $this
     */
    public function setOwner(BusinessUnit $owner = null)
    {
        $this->owner = $owner;

        return $this;
    }
}
