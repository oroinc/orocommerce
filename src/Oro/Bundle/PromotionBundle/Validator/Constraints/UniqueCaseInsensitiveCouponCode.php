<?php

namespace Oro\Bundle\PromotionBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Represents a validation constraint to ensure that coupon codes are unique and case-insensitive.
 * This constraint checks that no duplicate coupon codes exist, regardless of letter casing.
 */
class UniqueCaseInsensitiveCouponCode extends Constraint
{
    public string $message = 'oro.promotion.coupon.validators.case_insensitive_duplicate_found';

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
