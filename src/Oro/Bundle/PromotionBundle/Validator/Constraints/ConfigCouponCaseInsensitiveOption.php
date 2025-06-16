<?php

namespace Oro\Bundle\PromotionBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint for validating that case-insensitive coupon codes do not conflict within the given organization.
 *
 * This constraint is intended to be used in the system configuration to ensure
 * that enabling case-insensitive coupon code handling does not introduce duplicates
 * within the scope of a specific organization.
 */
class ConfigCouponCaseInsensitiveOption extends Constraint
{
    public string $message = 'oro.promotion.coupon.validators.case_insensitive_duplicates_found';
}
