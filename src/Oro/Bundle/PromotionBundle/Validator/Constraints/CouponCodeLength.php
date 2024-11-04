<?php

namespace Oro\Bundle\PromotionBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class CouponCodeLength extends Constraint
{
    /** @var string */
    public $message = 'oro.promotion.coupon.validators.max_possible_code_length_exceeded';

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }

    #[\Override]
    public function validatedBy(): string
    {
        return CouponCodeLengthValidator::ALIAS;
    }
}
