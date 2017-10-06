<?php

namespace Oro\Bundle\PromotionBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class CouponCodeLength extends Constraint
{
    /** @var string */
    public $message = 'oro.promotion.coupon.validators.max_possible_code_length_exceeded';

    /**
     * @inheritDoc
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    /**
     * @inheritDoc
     */
    public function validatedBy()
    {
        return CouponCodeLengthValidator::ALIAS;
    }
}
