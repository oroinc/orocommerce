<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class CurrencyExpression extends Constraint
{
    const ALIAS = 'orob2b_pricing.validator_constraints.currency_expression_validator';

    /**
     * @var string
     */
    public $message = 'oro.pricing.validators.field_are_not_allowed.message';

    /**
     * @return string
     */
    public function validatedBy()
    {
        return self::ALIAS;
    }
}
