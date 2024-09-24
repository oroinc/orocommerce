<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validate price rule expressions.
 * Check that expressions may be converted to a valid SQL.
 */
class PriceRuleExpressions extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.pricing.validators.invalid_price_rule_expression.message';

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
