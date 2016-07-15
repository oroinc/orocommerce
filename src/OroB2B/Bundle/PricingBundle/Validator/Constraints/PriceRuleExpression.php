<?php

namespace OroB2B\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class PriceRuleExpression extends Constraint
{
    /**
     * @var string
     */
    public $message = 'orob2b.pricing.validators.product_price.expression_is_invalid.message';

    /**
     * @var bool
     */
    public $withRelations = false;

    /**
     * @var bool
     */
    public $numericOnly = false;

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'orob2b_pricing.validator_constraints.price_rule_expression_validator';
    }
}
