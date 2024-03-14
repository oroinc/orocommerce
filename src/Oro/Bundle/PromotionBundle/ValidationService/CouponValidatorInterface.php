<?php

namespace Oro\Bundle\PromotionBundle\ValidationService;

use Oro\Bundle\PromotionBundle\Entity\Coupon;

/**
 * Coupon validator that returns error message if coupon cannot be applied.
 */
interface CouponValidatorInterface
{
    /**
     * Returns violation messages if coupon cannot be applied or empty array if coupon is valid.
     */
    public function getViolationMessages(Coupon $coupon, object $entity): array;
}
