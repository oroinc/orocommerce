<?php

namespace OroB2B\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class PriceRuleConditionalExpression extends Constraint
{
    /**
     * @var string
     */
    public $message = 'orob2b.pricing.validators.product_price.expression_is_invalid.message';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'orob2b_pricing.validator_constraints.price_rule_conditional_expression_validator';
    }
}
