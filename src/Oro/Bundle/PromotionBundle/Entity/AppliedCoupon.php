<?php

namespace Oro\Bundle\PromotionBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroPromotionBundle_Entity_AppliedCoupon;
use Oro\Bundle\EntityBundle\EntityProperty\CreatedAtAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\CreatedAtAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * Store Applied Coupon in database.
 *
 *
 * @method Order getOrder()
 * @method setOrder(Order $order)
 * @mixin OroPromotionBundle_Entity_AppliedCoupon
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_promotion_applied_coupon')]
#[Config]
class AppliedCoupon implements CreatedAtAwareInterface, ExtendEntityInterface
{
    use CreatedAtAwareTrait;
    use ExtendEntityTrait;

    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'coupon_code', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $couponCode = null;

    #[ORM\Column(name: 'source_promotion_id', type: Types::INTEGER, nullable: false)]
    protected ?int $sourcePromotionId = null;

    #[ORM\Column(name: 'source_coupon_id', type: Types::INTEGER, nullable: false)]
    protected ?int $sourceCouponId = null;

    #[ORM\OneToOne(inversedBy: 'appliedCoupon', targetEntity: AppliedPromotion::class)]
    #[ORM\JoinColumn(name: 'applied_promotion_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?AppliedPromotion $appliedPromotion = null;

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
