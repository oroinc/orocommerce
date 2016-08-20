<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class LogicalExpression extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.pricing.validators.logical_expression.message';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'orob2b_pricing.validator_constraints.logical_expression_validator';
    }
}
