<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validate price list product assignment rule expression.
 * Check that expression may be converted to a valid SQL.
 */
class ProductAssignmentRuleExpression extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.pricing.validators.invalid_product_assignment_rule_expression.message';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
