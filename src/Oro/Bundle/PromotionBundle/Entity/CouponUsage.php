<?php

namespace Oro\Bundle\PromotionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;

/**
 * @ORM\Table(name="oro_promotion_coupon_usage")
 * @ORM\Entity(repositoryClass="Oro\Bundle\PromotionBundle\Entity\Repository\CouponUsageRepository")
 */
class CouponUsage
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Coupon
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\PromotionBundle\Entity\Coupon")
     * @ORM\JoinColumn(name="coupon_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $coupon;

    /**
     * @var Promotion
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\PromotionBundle\Entity\Promotion")
     * @ORM\JoinColumn(name="promotion_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $promotion;

    /**
     * @var CustomerUser|null
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\CustomerUser")
     * @ORM\JoinColumn(name="customer_user_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $customerUser;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Coupon
     */
    public function getCoupon()
    {
        return $this->coupon;
    }

    /**
     * @param Coupon $coupon
     * @return $this
     */
    public function setCoupon(Coupon $coupon)
    {
        $this->coupon = $coupon;

        return $this;
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
     * @return $this
     */
    public function setPromotion(Promotion $promotion)
    {
        $this->promotion = $promotion;

        return $this;
    }

    /**
     * @return CustomerUser|null
     */
    public function getCustomerUser()
    {
        return $this->customerUser;
    }

    /**
     * @param CustomerUser|null $customerUser
     * @return $this
     */
    public function setCustomerUser(CustomerUser $customerUser = null)
    {
        $this->customerUser = $customerUser;

        return $this;
    }
}
