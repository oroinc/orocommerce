<?php

namespace Oro\Bundle\PromotionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\CreatedAtAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\CreatedAtAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * Store Applied Coupon in database.
 *
 * @Config()
 * @ORM\Table(name="oro_promotion_applied_coupon")
 * @ORM\Entity
 *
 * @method Order getOrder()
 * @method setOrder(Order $order)
 */
class AppliedCoupon implements CreatedAtAwareInterface, ExtendEntityInterface
{
    use CreatedAtAwareTrait;
    use ExtendEntityTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var integer
     */
    protected $id;

    /**
     * @ORM\Column(name="coupon_code", type="string", length=255, nullable=false)
     *
     * @var string
     */
    protected $couponCode;

    /**
     * @ORM\Column(name="source_promotion_id", type="integer", nullable=false)
     * @var int
     */
    protected $sourcePromotionId;

    /**
     * @ORM\Column(name="source_coupon_id", type="integer", nullable=false)
     * @var int|null
     */
    protected $sourceCouponId;

    /**
     * @var AppliedPromotion|null
     *
     * @ORM\OneToOne(targetEntity="Oro\Bundle\PromotionBundle\Entity\AppliedPromotion", inversedBy="appliedCoupon")
     * @ORM\JoinColumn(name="applied_promotion_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     */
    protected $appliedPromotion;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCouponCode()
    {
        return $this->couponCode;
    }

    /**
     * @param string $couponCode
     * @return $this
     */
    public function setCouponCode($couponCode)
    {
        $this->couponCode = $couponCode;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getSourcePromotionId()
    {
        return $this->sourcePromotionId;
    }

    /**
     * @param int $sourcePromotionId
     * @return $this
     */
    public function setSourcePromotionId($sourcePromotionId)
    {
        $this->sourcePromotionId = (int)$sourcePromotionId;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getSourceCouponId()
    {
        return $this->sourceCouponId;
    }

    /**
     * @param int|null $sourceCouponId
     * @return $this
     */
    public function setSourceCouponId($sourceCouponId)
    {
        $this->sourceCouponId = (int)$sourceCouponId;

        return $this;
    }

    /**
     * @return AppliedPromotion
     */
    public function getAppliedPromotion()
    {
        return $this->appliedPromotion;
    }

    /**
     * @param AppliedPromotion $appliedPromotion
     * @return $this
     */
    public function setAppliedPromotion(AppliedPromotion $appliedPromotion)
    {
        $this->appliedPromotion = $appliedPromotion;

        return $this;
    }
}
