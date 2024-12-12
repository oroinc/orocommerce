<?php

namespace Oro\Bundle\PromotionBundle\Model;

use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\Promotion;

/**
 * Represents a coupon applied to an entity.
 * This model is used on the storefront to minimize code duplication and performance issues.
 */
final readonly class FrontendAppliedCoupon
{
    public function __construct(
        private AppliedCoupon $appliedCoupon,
        private Promotion $promotion
    ) {
    }

    public function getAppliedCoupon(): AppliedCoupon
    {
        return $this->appliedCoupon;
    }

    public function getPromotion(): Promotion
    {
        return $this->promotion;
    }
}
