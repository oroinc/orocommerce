<?php

namespace Oro\Bundle\PromotionBundle\ValidationService;

use Oro\Bundle\PromotionBundle\Entity\Coupon;

/**
 * Coupon validator that returns error message if coupon cannot be applied.
 */
interface CouponValidatorInterface
{
    /**
     * Returns violation message if coupon cannot be applied.
     */
    public function getViolation(Coupon $coupon, object $entity): ?string;
}
