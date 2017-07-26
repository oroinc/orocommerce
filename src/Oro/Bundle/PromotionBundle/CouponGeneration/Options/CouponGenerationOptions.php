<?php

namespace  Oro\Bundle\PromotionBundle\CouponGeneration\Options;

use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;

/**
 * Simple DTO that used to transfer coupon generation options through different code layers.
 */
class CouponGenerationOptions
{
    const NUMERIC_CODE_TYPE = 'numeric';
    const ALPHANUMERIC_CODE_TYPE = 'alphanumeric';
    const ALPHABETIC_CODE_TYPE = 'alphabetic';

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
     * @var int
     */
    protected $codeLength = 12;

    /**
     * @var string
     */
    protected $codeType;

    /**
     * @var string
     */
    protected $codePrefix;

    /**
     * @var string
     */
    protected $codeSuffix;

    /**
     * @var int
     */
    protected $dashesSequence;

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
     * @return int
     */
    public function getCodeLength()
    {
        return $this->codeLength;
    }

    /**
     * @param int $codeLength
     */
    public function setCodeLength($codeLength)
    {
        $this->codeLength = $codeLength;
    }

    /**
     * @return string
     */
    public function getCodeType()
    {
        return $this->codeType;
    }

    /**
     * @param string $codeType
     */
    public function setCodeType($codeType)
    {
        $this->codeType = $codeType;
    }

    /**
     * @return string
     */
    public function getCodePrefix()
    {
        return $this->codePrefix;
    }

    /**
     * @param string $codePrefix
     */
    public function setCodePrefix($codePrefix)
    {
        $this->codePrefix = $codePrefix;
    }

    /**
     * @return string
     */
    public function getCodeSuffix()
    {
        return $this->codeSuffix;
    }

    /**
     * @param string $codeSuffix
     */
    public function setCodeSuffix($codeSuffix)
    {
        $this->codeSuffix = $codeSuffix;
    }

    /**
     * @return int
     */
    public function getDashesSequence()
    {
        return $this->dashesSequence;
    }

    /**
     * @param int $dashesSequence
     */
    public function setDashesSequence($dashesSequence)
    {
        $this->dashesSequence = $dashesSequence;
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
