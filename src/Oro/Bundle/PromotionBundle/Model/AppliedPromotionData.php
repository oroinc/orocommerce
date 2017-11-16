<?php

namespace Oro\Bundle\PromotionBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Selectable;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\RuleBundle\Entity\RuleInterface;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\SegmentBundle\Entity\Segment;

class AppliedPromotionData implements PromotionDataInterface
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var RuleInterface
     */
    protected $rule;

    /**
     * @var Collection|Scope[]
     */
    protected $scopes;

    /**
     * @var DiscountConfiguration
     */
    protected $discountConfiguration;

    /**
     * @var bool
     */
    protected $useCoupons = false;

    /**
     * @var Collection|Selectable|Coupon[]
     */
    protected $coupons;

    /**
     * @var Segment
     */
    protected $productsSegment;

    public function __construct()
    {
        $this->scopes = new ArrayCollection();
        $this->coupons = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function getDiscountConfiguration()
    {
        return $this->discountConfiguration;
    }

    /**
     * @param DiscountConfiguration $discountConfiguration
     * @return $this
     */
    public function setDiscountConfiguration($discountConfiguration)
    {
        $this->discountConfiguration = $discountConfiguration;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isUseCoupons()
    {
        return $this->useCoupons;
    }

    /**
     * @param bool $useCoupons
     * @return $this
     */
    public function setUseCoupons($useCoupons)
    {
        $this->useCoupons = $useCoupons;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCoupons()
    {
        return $this->coupons;
    }

    /**
     * @param Coupon $coupon
     * @return $this
     */
    public function addCoupon(Coupon $coupon)
    {
        if (!$this->coupons->contains($coupon)) {
            $this->coupons->add($coupon);
        }

        return $this;
    }

    /**
     * @return Segment
     */
    public function getProductsSegment()
    {
        return $this->productsSegment;
    }

    /**
     * @param Segment $productsSegment
     * @return $this
     */
    public function setProductsSegment(Segment $productsSegment)
    {
        $this->productsSegment = $productsSegment;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * @param RuleInterface $rule
     * @return $this
     */
    public function setRule($rule)
    {
        $this->rule = $rule;

        return $this;
    }

    /**
     * @return Collection|Scope[]
     */
    public function getScopes()
    {
        return $this->scopes;
    }

    /**
     * @param Scope $scope
     * @return $this
     */
    public function addScope(Scope $scope)
    {
        if (!$this->scopes->contains($scope)) {
            $this->scopes->add($scope);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return AppliedPromotionData
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSchedules()
    {
        return new ArrayCollection([]);
    }
}
